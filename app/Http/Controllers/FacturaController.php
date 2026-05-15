<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Services\WaterBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FacturaController extends Controller
{
    public function __construct(private WaterBillingService $waterBilling)
    {
    }

    public function index(Request $request)
    {
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

        if ($request->filled('buscar')) {
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

        return view('facturas.index', [
            'facturas' => $query->simplePaginate(12)->withQueryString(),
            'periodos' => Cache::remember('facturas.periodos', now()->addMinutes(10), function () {
                return PeriodoFacturacion::select('id_periodo', 'nombre', 'fecha_inicio')
                    ->orderByDesc('fecha_inicio')
                    ->get();
            }),
            'totales' => Cache::remember('facturas.totales', now()->addMinutes(2), function () {
                return [
                    'pendientes' => Factura::whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count(),
                    'pagadas' => Factura::where('estado', 'pagada')->count(),
                    'monto_total' => Factura::where('estado', '!=', 'anulada')->sum('total'),
                ];
            }),
            'candidatos' => $this->billingCandidates(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_socio' => ['required', 'exists:socios,id_socio'],
        ]);

        $socio = Socio::with(['persona', 'tarifa', 'medidorActivo'])->findOrFail($data['id_socio']);
        $medidor = $socio->medidorActivo;

        if (!$medidor) {
            return back()->with('error', 'El socio no tiene un medidor activo para facturar.');
        }

        $lectura = Lectura::where('id_medidor', $medidor->id_medidor)
            ->whereDoesntHave('facturas')
            ->orderByDesc('fecha_lectura')
            ->first();

        if (!$lectura) {
            return back()->with('error', 'No existe una lectura pendiente de facturacion para este socio.');
        }

        $tarifa = $socio->tarifa;

        if (!$tarifa) {
            return back()->with('error', 'El socio no tiene una tarifa asignada.');
        }

        $periodo = $this->resolvePeriodo($socio, $medidor->fecha_instalacion, $lectura->fecha_lectura);
        $saldoPendiente = $this->pendingBalance($socio->id_socio);
        $desgloseTarifa = $tarifa->calcularDesglose((float) $lectura->consumo_m3);
        $montoConsumo = $desgloseTarifa['water_charge'];
        $recargoMora = ($saldoPendiente > 0 ? round($saldoPendiente * 0.02, 2) : 0) + $desgloseTarifa['cutoff_penalty'];

        $factura = Factura::create([
            'numero_factura' => $this->nextNumeroFactura(),
            'fecha_emision' => now()->toDateString(),
            'consumo_m3' => $lectura->consumo_m3,
            'monto_consumo' => $montoConsumo,
            'cargo_fijo' => $desgloseTarifa['sewer_fixed_charge'],
            'recargo_mora' => $recargoMora,
            'descuentos' => 0,
            'precio_m3_aplicado' => $desgloseTarifa['excess_rate'],
            'cargo_fijo_aplicado' => $desgloseTarifa['fixed_charge'],
            'estado' => $saldoPendiente > 0 ? 'vencida' : 'pendiente',
            'id_socio' => $socio->id_socio,
            'id_lectura' => $lectura->id_lectura,
            'id_periodo' => $periodo->id_periodo,
        ]);

        Cache::forget('facturas.totales');
        Cache::forget('facturas.billing_candidates');

        return redirect()
            ->route('secretaria.facturas.show', $factura)
            ->with('success', 'Factura generada correctamente para ' . $socio->persona?->nombre_completo . '.');
    }

    public function show(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);
        $pdfUrl = route('secretaria.facturas.pdf', $factura);
        $printUrl = route('secretaria.facturas.print', $factura);
        $shareMessage = "Factura {$factura->numero_factura} de {$factura->socio?->persona?->nombre_completo}. PDF: {$pdfUrl}";
        $gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1'
            . '&to=' . urlencode((string) $factura->socio?->persona?->email)
            . '&su=' . urlencode('Factura ' . $factura->numero_factura)
            . '&body=' . urlencode($shareMessage);
        $whatsappUrl = 'https://wa.me/?text=' . urlencode($shareMessage);

        return view('facturas.show', [
            'factura' => $factura,
            'company' => SystemSetting::getValue('general', []),
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
            'pdfUrl' => $pdfUrl,
            'printUrl' => $printUrl,
            'gmailUrl' => $gmailUrl,
            'whatsappUrl' => $whatsappUrl,
        ]);
    }

    public function pdf(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);
        $company = SystemSetting::getValue('general', []);
        $viewData = [
            'factura' => $factura,
            'company' => $company,
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'companyLogoDataUri' => $this->buildPdfLogoDataUri($company),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
        ];

        try {
            return Pdf::loadView('facturas.pdf', $viewData)->download("{$factura->numero_factura}.pdf");
        } catch (\Throwable $exception) {
            report($exception);

            $viewData['companyLogoDataUri'] = null;

            return Pdf::loadView('facturas.pdf', $viewData)->download("{$factura->numero_factura}.pdf");
        }
    }

    public function print(Factura $factura)
    {
        $factura->load([
            'socio.persona',
            'socio.sector',
            'socio.tarifa',
            'periodo',
            'lectura.medidor',
            'cobros.metodoPago',
            'cobros.empleado.persona',
        ]);

        $pagado = (float) $factura->cobros
            ->where('estado', '!=', 'anulado')
            ->sum('monto_pagado');
        $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
        $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

        return view('facturas.print', [
            'factura' => $factura,
            'company' => SystemSetting::getValue('general', []),
            'billingBreakdown' => $this->buildBillingBreakdown($factura),
            'resumenCobro' => [
                'subtotal' => $subtotal,
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
        ]);
    }

    private function billingCandidates()
    {
        return Cache::remember('facturas.billing_candidates', now()->addMinutes(2), function () {
            return Socio::with(['persona', 'tarifa', 'medidorActivo'])
                ->where('estado', '!=', 'inactivo')
                ->get()
                ->filter(fn ($socio) => $socio->medidorActivo && $socio->tarifa)
                ->map(function ($socio) {
                    $lectura = Lectura::where('id_medidor', $socio->medidorActivo->id_medidor)
                        ->whereDoesntHave('facturas')
                        ->orderByDesc('fecha_lectura')
                        ->first();

                    if (!$lectura) {
                        return null;
                    }

                    $ultimoPago = Factura::with('periodo')
                        ->where('id_socio', $socio->id_socio)
                        ->where('estado', 'pagada')
                        ->orderByDesc('fecha_pago')
                        ->first();

                    $inicio = $ultimoPago?->periodo?->fecha_fin
                        ? $ultimoPago->periodo->fecha_fin->copy()->addDay()
                        : ($socio->medidorActivo->fecha_instalacion ?? $socio->fecha_registro);

                    return (object) [
                        'id_socio' => $socio->id_socio,
                        'nombre_completo' => $socio->persona?->nombre_completo ?? 'Sin socio',
                        'codigo_display' => $socio->codigo_display,
                        'tarifa_nombre' => $socio->tarifa?->nombre,
                        'tipo_uso' => $socio->tarifa?->tipo_uso ?? 'domestico',
                        'fecha_inicio' => optional($inicio)?->format('d/m/Y'),
                        'fecha_instalacion' => optional($socio->medidorActivo->fecha_instalacion)?->format('d/m/Y'),
                        'fecha_lectura' => optional($lectura->fecha_lectura)?->format('d/m/Y'),
                        'consumo_m3' => (float) $lectura->consumo_m3,
                        'saldo_pendiente' => $this->pendingBalance($socio->id_socio),
                    ];
                })
                ->filter()
                ->sortByDesc('fecha_lectura')
                ->values();
        });
    }

    private function pendingBalance(int $idSocio): float
    {
        return Factura::where('id_socio', $idSocio)
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->get()
            ->sum(function ($factura) {
                $pagado = $factura->cobros()->where('estado', '!=', 'anulado')->sum('monto_pagado');

                return max(0, (float) $factura->total - (float) $pagado);
            });
    }

    private function resolvePeriodo(Socio $socio, $fechaInstalacion, $fechaLectura): PeriodoFacturacion
    {
        $ultimoPago = Factura::with('periodo')
            ->where('id_socio', $socio->id_socio)
            ->where('estado', 'pagada')
            ->orderByDesc('fecha_pago')
            ->first();

        $inicio = $ultimoPago?->periodo?->fecha_fin
            ? $ultimoPago->periodo->fecha_fin->copy()->addDay()
            : ($fechaInstalacion ?? $socio->fecha_registro ?? now()->toDateString());

        $inicio = \Illuminate\Support\Carbon::parse($inicio);
        $fin = \Illuminate\Support\Carbon::parse($fechaLectura);

        if ($fin->lt($inicio)) {
            $inicio = $fin->copy();
        }

        return PeriodoFacturacion::firstOrCreate(
            [
                'nombre' => $fin->translatedFormat('F Y'),
            ],
            [
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'cerrado' => false,
            ]
        );
    }

    private function nextNumeroFactura(): string
    {
        $next = ((int) Factura::max('id_factura')) + 1;

        return 'FAC-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function buildBillingBreakdown(Factura $factura): array
    {
        $base = $this->waterBilling->breakdown((float) $factura->consumo_m3);
        $cutoffPenalty = $base['cutoff_penalty'];
        $moraSaldoAnterior = max(0, round((float) $factura->recargo_mora - $cutoffPenalty, 2));

        return $base + [
            'previous_reading' => (float) ($factura->lectura?->lectura_anterior ?? 0),
            'current_reading' => (float) ($factura->lectura?->lectura_actual ?? 0),
            'consumed_m3' => (float) $factura->consumo_m3,
            'mora_saldo_anterior' => $moraSaldoAnterior,
            'codigo_usuario' => $factura->socio?->codigo_display ?? ('SOC-' . str_pad((string) $factura->id_socio, 4, '0', STR_PAD_LEFT)),
        ];
    }

    private function buildPdfLogoDataUri(array $company): ?string
    {
        if (empty($company['company_logo'])) {
            return null;
        }

        $relative = str_replace('storage/', '', $company['company_logo']);
        $resolved = storage_path('app/public/' . $relative);

        if (!file_exists($resolved) || !is_readable($resolved)) {
            return null;
        }

        $mimeType = mime_content_type($resolved) ?: 'image/png';
        $contents = @file_get_contents($resolved);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }
}
