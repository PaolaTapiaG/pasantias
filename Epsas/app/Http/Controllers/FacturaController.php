<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Services\BillingAutomationService;
use App\Services\WaterBillingService;
use App\Support\OperationalCache;
use App\Support\InvoiceBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    private const INVOICE_DETAIL_RELATIONS = [
        'socio.persona',
        'socio.sector',
        'socio.tarifa',
        'socio.medidorActivo',
        'periodo',
        'lectura.medidor',
        'cobros.metodoPago',
        'cobros.empleado.persona',
    ];

    public function __construct(
        private WaterBillingService $waterBilling,
        private BillingAutomationService $billingAutomation
    ) {}

    public function index(Request $request)
    {
        return view('facturas.index', [
            'facturas' => $this->invoicePaginator($request),
            'periodos' => $this->periodOptions(),
            'totales' => $this->invoiceTotals(),
            'candidatos' => $this->billingCandidates(),
            'syncResult' => session('billing_sync_result', ['created' => 0, 'skipped' => 0]),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/facturas', 'GET');

        $this->invoicePaginator($request, url('/admin/facturas'));
        $this->periodOptions();
        $this->invoiceTotals();
        $this->billingCandidates();
    }

    private function invoicePaginator(Request $request, ?string $path = null)
    {
        $perPage = $this->perPage($request, 50);
        $query = DB::table('facturas as f')
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->select([
                'f.id_factura',
                'f.numero_factura',
                'f.fecha_emision',
                'f.consumo_m3',
                'f.total',
                'f.estado',
                's.id_socio',
                's.numero_socio',
                'pf.nombre as periodo_nombre',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->selectRaw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo_display")
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.id_factura');

        if ($request->filled('buscar') && strlen(trim((string) $request->buscar)) >= 2) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('f.numero_factura', 'ilike', "%{$term}%")
                    ->orWhere('f.estado', 'ilike', "%{$term}%")
                    ->orWhere('s.numero_socio', 'ilike', "%{$term}%")
                    ->orWhere('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('f.estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('f.id_periodo', $request->periodo);
        }

        $cacheKey = 'facturas.index.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('billing', $cacheKey, fn () => $query
            ->simplePaginate($perPage)
            ->withPath($path ?? url('/admin/facturas'))
            ->appends($request->query())
            ->through(function ($factura) {
                $factura->fecha_emision = $factura->fecha_emision ? Carbon::parse($factura->fecha_emision) : null;

                return $factura;
            }));
    }

    private function perPage(Request $request, int $default = 50): int
    {
        return min(max($request->integer('per_page', $default), 10), 100);
    }

    private function periodOptions()
    {
        return OperationalCache::rememberDomain('billing', 'facturas.periodos', function () {
            return PeriodoFacturacion::select('id_periodo', 'nombre', 'fecha_inicio')
                ->orderByDesc('fecha_inicio')
                ->get();
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_socio' => ['required', 'integer'],
        ]);

        $socio = Socio::with(['persona', 'tarifa', 'medidorActivo'])->findOrFail($data['id_socio']);
        $medidor = $socio->medidorActivo;

        if (! $medidor) {
            return back()->with('error', 'El socio no tiene un medidor activo para facturar.');
        }

        if ($socio->estado !== 'activo') {
            return back()->with('error', 'El socio aun no esta activo para facturacion. Valida primero la instalacion del medidor.');
        }

        if (! $medidor->fecha_instalacion) {
            return back()->with('error', 'El medidor no tiene instalacion confirmada. No se puede generar factura todavia.');
        }

        $tarifa = $socio->tarifa;

        if (! $tarifa) {
            return back()->with('error', 'El socio no tiene una tarifa asignada.');
        }

        try {
            $factura = DB::transaction(function () use ($socio, $medidor, $tarifa) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::select("SELECT pg_advisory_xact_lock(hashtext('invoice-number-sequence'))");
                }

                $nextChargeStart = $this->billingAutomation->nextChargeStartForSocio(
                    $socio->id_socio,
                    (string) ($medidor->fecha_instalacion ?: $socio->fecha_registro ?: now()->toDateString())
                );
                $lectura = Lectura::query()
                    ->where('id_medidor', $medidor->id_medidor)
                    ->whereDoesntHave('facturas')
                    ->whereDate('fecha_lectura', '>=', $nextChargeStart->toDateString())
                    ->orderBy('fecha_lectura')
                    ->orderBy('id_lectura')
                    ->lockForUpdate()
                    ->first();

                if (! $lectura || Factura::where('id_lectura', $lectura->id_lectura)->exists()) {
                    throw new \RuntimeException('No existe una lectura pendiente de facturacion para este socio.');
                }

                $fechaLectura = Carbon::parse($lectura->fecha_lectura);
                $periodo = $this->resolvePeriodo($fechaLectura);
                $inicioCobro = $this->resolveInicioCobro($socio, $medidor->fecha_instalacion, $fechaLectura);

                if ($inicioCobro->gt($fechaLectura)) {
                    throw new \RuntimeException('La lectura pendiente no puede generar un periodo anterior al ultimo ya facturado.');
                }

                $saldoPendiente = $this->pendingBalance($socio->id_socio);
                $desgloseTarifa = $tarifa->calcularDesglose((float) $lectura->consumo_m3);
                $montoConsumo = $desgloseTarifa['water_charge'];
                $recargoMora = ($saldoPendiente > 0 ? round($saldoPendiente * 0.02, 2) : 0) + $desgloseTarifa['cutoff_penalty'];

                return Factura::create([
                    'numero_factura' => $this->nextNumeroFactura(),
                    'fecha_emision' => now()->toDateString(),
                    'fecha_inicio_cobro' => $inicioCobro->toDateString(),
                    'fecha_fin_cobro' => $fechaLectura->toDateString(),
                    'consumo_m3' => $lectura->consumo_m3,
                    'monto_consumo' => $montoConsumo,
                    'cargo_fijo' => $desgloseTarifa['sewer_fixed_charge'],
                    'recargo_mora' => $recargoMora,
                    'descuentos' => 0,
                    'precio_m3_aplicado' => $desgloseTarifa['excess_rate'],
                    'cargo_fijo_aplicado' => $desgloseTarifa['fixed_charge'],
                    'consumo_minimo_m3_aplicado' => $desgloseTarifa['included_m3'],
                    'umbral_corte_m3_aplicado' => $desgloseTarifa['cutoff_threshold_m3'],
                    'tarifa_reconexion_aplicada' => $desgloseTarifa['reconnection_fee'],
                    'estado' => $saldoPendiente > 0 ? 'vencida' : 'pendiente',
                    'id_socio' => $socio->id_socio,
                    'id_lectura' => $lectura->id_lectura,
                    'id_periodo' => $periodo->id_periodo,
                ]);
            }, 3);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        OperationalCache::bumpDomain('billing');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        OperationalCache::forget('billing:signals');
        OperationalCache::forget('corte:open-socios');
        OperationalCache::forget('reconexion:open-socios');
        OperationalCache::forget('reconexion:latest-cuts');
        OperationalCache::forget('api-dashboard-tecnico');

        return redirect()
            ->route('secretaria.facturas.show', $factura)
            ->with('success', 'Factura generada correctamente para '.$socio->persona?->nombre_completo.'.');
    }

    public function show(Factura $factura)
    {
        $factura = $this->loadInvoiceDetail($factura);

        $pdfUrl = route('secretaria.facturas.pdf', $factura);
        $printUrl = route('secretaria.facturas.print', $factura);
        $shareMessage = "Hola {$factura->socio?->persona?->nombre_completo}, tu factura {$factura->numero_factura} ya esta lista. Te la enviaremos como PDF adjunto por correo o puedes solicitarla en administracion.";
        $whatsappUrl = $this->whatsappUrl($factura->socio?->persona?->telefono, $shareMessage);

        return view('facturas.show', array_merge($this->invoiceViewData($factura), [
            'pdfUrl' => $pdfUrl,
            'printUrl' => $printUrl,
            'whatsappUrl' => $whatsappUrl,
        ]));
    }

    public function pdf(Factura $factura)
    {
        $factura = $this->loadInvoiceDetail($factura);
        $viewData = $this->invoicePdfViewData($factura);

        try {
            return Pdf::loadView('facturas.pdf', $viewData)
                ->setPaper('letter', 'portrait')
                ->download("{$factura->numero_factura}.pdf");
        } catch (\Throwable $exception) {
            report($exception);

            $viewData['companyLogoDataUri'] = null;

            return Pdf::loadView('facturas.pdf', $viewData)
                ->setPaper('letter', 'portrait')
                ->download("{$factura->numero_factura}.pdf");
        }
    }

    public function print(Factura $factura)
    {
        $factura = $this->loadInvoiceDetail($factura);

        return view('facturas.print', $this->invoiceViewData($factura));
    }

    private function billingCandidates()
    {
        return OperationalCache::rememberDomain('billing', 'facturas.billing_candidates', function () {
            $oldestUnbilledReadings = DB::table('lecturas as l')
                ->leftJoin('facturas as lf', 'lf.id_lectura', '=', 'l.id_lectura')
                ->whereNull('lf.id_factura')
                ->selectRaw('DISTINCT ON (l.id_medidor) l.id_medidor, l.id_lectura, l.fecha_lectura, l.consumo_m3')
                ->orderBy('l.id_medidor')
                ->orderBy('l.fecha_lectura')
                ->orderBy('l.id_lectura');

            $lastInvoiceEnds = DB::table('facturas as f')
                ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
                ->groupBy('f.id_socio')
                ->selectRaw('f.id_socio, MAX(COALESCE(f.fecha_fin_cobro, pf.fecha_fin)) as ultimo_fin');

            $pagosPorFactura = DB::table('cobros')
                ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                ->groupBy('id_factura');

            $saldosPendientes = DB::table('facturas as f')
                ->leftJoinSub($pagosPorFactura, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
                ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
                ->groupBy('f.id_socio')
                ->selectRaw('f.id_socio, ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as saldo_pendiente');

            return DB::table('socios as s')
                ->join('personas as p', 'p.id_persona', '=', 's.id_persona')
                ->join('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
                ->join('medidores as m', function ($join) {
                    $join->on('m.id_socio', '=', 's.id_socio')
                        ->where('m.estado', '=', 'activo');
                })
                ->joinSub($oldestUnbilledReadings, 'lr', fn ($join) => $join->on('lr.id_medidor', '=', 'm.id_medidor'))
                ->leftJoinSub($lastInvoiceEnds, 'li', fn ($join) => $join->on('li.id_socio', '=', 's.id_socio'))
                ->leftJoinSub($saldosPendientes, 'sp', fn ($join) => $join->on('sp.id_socio', '=', 's.id_socio'))
                ->where('s.estado', 'activo')
                ->orderByDesc('lr.fecha_lectura')
                ->limit(20)
                ->get([
                    's.id_socio',
                    's.numero_socio',
                    's.fecha_registro',
                    'm.fecha_instalacion',
                    't.nombre as tarifa_nombre',
                    't.tipo_uso',
                    'lr.fecha_lectura',
                    'lr.consumo_m3',
                    'li.ultimo_fin',
                    DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
                    DB::raw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo_display"),
                    DB::raw('COALESCE(sp.saldo_pendiente, 0) as saldo_pendiente'),
                ])
                ->map(function ($row) {
                    $inicio = $row->ultimo_fin
                        ? Carbon::parse($row->ultimo_fin)->addDay()
                        : Carbon::parse($row->fecha_instalacion ?: $row->fecha_registro);

                    return (object) [
                        'id_socio' => $row->id_socio,
                        'nombre_completo' => $row->nombre_completo ?: 'Sin socio',
                        'codigo_display' => $row->codigo_display,
                        'tarifa_nombre' => $row->tarifa_nombre,
                        'tipo_uso' => $row->tipo_uso ?? 'domestico',
                        'fecha_inicio' => $inicio?->format('d/m/Y'),
                        'fecha_instalacion' => $row->fecha_instalacion ? Carbon::parse($row->fecha_instalacion)->format('d/m/Y') : null,
                        'fecha_lectura' => $row->fecha_lectura ? Carbon::parse($row->fecha_lectura)->format('d/m/Y') : null,
                        'consumo_m3' => (float) $row->consumo_m3,
                        'saldo_pendiente' => (float) $row->saldo_pendiente,
                    ];
                });
        });
    }

    private function invoiceTotals(): array
    {
        return OperationalCache::rememberDomain('billing', 'facturas.totales', function () {
            $summary = Factura::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE estado IN ('pendiente', 'parcial', 'vencida')) as pendientes,
                    COUNT(*) FILTER (WHERE estado = 'pagada') as pagadas,
                    COALESCE(SUM(CASE WHEN estado <> 'anulada' THEN total ELSE 0 END), 0) as monto_total
                ")
                ->first();

            return [
                'pendientes' => (int) ($summary?->pendientes ?? 0),
                'pagadas' => (int) ($summary?->pagadas ?? 0),
                'monto_total' => (float) ($summary?->monto_total ?? 0),
            ];
        });
    }

    private function pendingBalance(int $idSocio): float
    {
        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return (float) DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->selectRaw('ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 0), 2) as saldo')
            ->value('saldo');
    }

    private function resolvePeriodo(Carbon $fechaLectura): PeriodoFacturacion
    {
        return $this->billingAutomation->monthlyPeriodFor($fechaLectura);
    }

    private function resolveInicioCobro(Socio $socio, $fechaInstalacion, Carbon $fechaLectura): Carbon
    {
        $fallback = $fechaInstalacion ?: $socio->fecha_registro ?: now()->toDateString();
        $inicio = $this->billingAutomation->nextChargeStartForSocio($socio->id_socio, (string) $fallback);

        return $inicio->gt($fechaLectura) ? $fechaLectura->copy() : $inicio;
    }

    private function nextNumeroFactura(): string
    {
        $next = ((int) Factura::max('id_factura')) + 1;

        return 'FAC-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function buildBillingBreakdown(Factura $factura): array
    {
        $base = $this->waterBilling->breakdown((float) $factura->consumo_m3, [
            'included_m3' => (float) ($factura->consumo_minimo_m3_aplicado ?? 0),
            'fixed_charge' => (float) ($factura->cargo_fijo_aplicado ?? 0),
            'excess_rate' => (float) ($factura->precio_m3_aplicado ?? 0),
            'cutoff_threshold_m3' => (float) ($factura->umbral_corte_m3_aplicado ?? 0),
            'reconnection_fee' => (float) ($factura->tarifa_reconexion_aplicada ?? 0),
            'sewer_fixed_charge' => (float) $factura->cargo_fijo,
        ]);
        $cutoffPenalty = $base['cutoff_penalty'];
        $moraSaldoAnterior = max(0, round((float) $factura->recargo_mora - $cutoffPenalty, 2));

        return $base + [
            'previous_reading' => (float) ($factura->lectura?->lectura_anterior ?? 0),
            'current_reading' => (float) ($factura->lectura?->lectura_actual ?? 0),
            'consumed_m3' => (float) $factura->consumo_m3,
            'mora_saldo_anterior' => $moraSaldoAnterior,
            'codigo_usuario' => $factura->socio?->codigo_display ?? ('SOC-'.str_pad((string) $factura->id_socio, 4, '0', STR_PAD_LEFT)),
            'numero_medidor' => $factura->lectura?->medidor?->numero_serie
                ?? $factura->socio?->medidorActivo?->numero_serie
                ?? 'Sin medidor',
        ];
    }

    private function loadInvoiceDetail(Factura $factura): Factura
    {
        return OperationalCache::remember(
            "factura:detail:{$factura->id_factura}",
            fn () => Factura::query()
                ->with(self::INVOICE_DETAIL_RELATIONS)
                ->findOrFail($factura->id_factura),
            now()->addHours(6)
        );
    }

    private function invoiceViewData(Factura $factura): array
    {
        $company = array_merge([
            'company_name' => 'EPSAS',
            'company_logo' => null,
            'address' => null,
            'company_phone' => null,
            'company_email' => null,
        ], SystemSetting::getValue('general', []));

        $company['company_logo'] = InvoiceBranding::approvedLogoPath($company);

        return [
            'factura' => $factura,
            'company' => $company,
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'resumenCobro' => $this->invoicePaymentSummary($factura),
            'facturasAdeudadas' => $this->invoiceDebtHistory($factura),
        ];
    }

    private function invoiceDebtHistory(Factura $factura)
    {
        return OperationalCache::rememberDomain('billing', 'facturas.debt-history.v2.'.$factura->id_factura, function () use ($factura) {
            $payments = DB::table('cobros')
                ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                ->groupBy('id_factura');

            return DB::table('facturas as f')
                ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
                ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
                ->where('f.id_socio', $factura->id_socio)
                ->where('f.id_factura', '<>', $factura->id_factura)
                ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
                ->whereRaw('GREATEST(f.total - COALESCE(cp.pagado, 0), 0) > 0')
                ->orderBy(DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin, f.fecha_emision)'))
                ->orderBy('f.id_factura')
                ->get([
                    'f.id_factura',
                    'f.numero_factura',
                    'f.fecha_emision',
                    'f.consumo_m3',
                    'f.monto_consumo',
                    'f.cargo_fijo',
                    'f.descuentos',
                    'f.recargo_mora',
                    'f.total',
                    'pf.nombre as periodo_nombre',
                    DB::raw('ROUND(GREATEST(f.total - COALESCE(cp.pagado, 0), 0), 2) as saldo_pendiente'),
                ]);
        });
    }

    private function invoicePaymentSummary(Factura $factura): array
    {
        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

        return [
            'subtotal' => $subtotal,
            'pagado' => $pagado,
            'pendiente' => $pendiente,
        ];
    }

    private function buildPdfLogoDataUri(array $company): ?string
    {
        return InvoiceBranding::dataUri($company);
    }

    private function invoicePdfViewData(Factura $factura): array
    {
        $viewData = $this->invoiceViewData($factura);
        $viewData['companyLogoDataUri'] = $this->buildPdfLogoDataUri($viewData['company']);

        return $viewData;
    }

    private function whatsappUrl(?string $phone, string $message): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return 'https://wa.me/?text='.urlencode($message);
        }

        if (strlen($digits) === 8) {
            $digits = '591'.$digits;
        }

        return 'https://wa.me/'.$digits.'?text='.urlencode($message);
    }
}
