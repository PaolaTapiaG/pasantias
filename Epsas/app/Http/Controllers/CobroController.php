<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Cobro;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\HistorialPago;
use App\Models\MetodoPago;
use App\Models\Socio;
use App\Services\PaymentAmountPolicy;
use App\Services\PaymentQrService;
use App\Support\CurrentEmployee;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CobroController extends Controller
{
    public function __construct(
        private PaymentAmountPolicy $paymentAmountPolicy,
        private PaymentQrService $paymentQrService
    ) {}

    public function index(Request $request)
    {
        return view('cobros.index', $this->paymentIndexData($request));
    }

    public function warmIndexCache(): void
    {
        $data = $this->paymentIndexData(Request::create('/admin/cobros', 'GET'));

        $this->warmPaymentDetails(
            collect($data['socios'] ?? [])
                ->pluck('id_socio')
                ->take(50)
                ->map(fn ($id) => (int) $id)
                ->all()
        );
    }

    private function paymentIndexData(Request $request): array
    {
        $search = trim((string) $request->query('buscar', ''));
        $search = strlen($search) >= 2 ? $search : '';
        $cacheKey = 'cobros.index.'.md5($search);
        $summaryKey = 'cobros.index.summary.'.md5($search);

        $socios = OperationalCache::rememberDomain('billing', $cacheKey, function () use ($search) {
            return $this->paymentDebtorsQuery($search)
                ->orderByRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) asc")
                ->limit($search !== '' ? 100 : 50)
                ->get()
                ->map(function ($row) {
                    return [
                        'id_socio' => $row->id_socio,
                        'nombre_completo' => $row->nombre_completo,
                        'codigo_display' => $row->numero_socio ?: ('SOC-'.str_pad((string) $row->id_socio, 4, '0', STR_PAD_LEFT)),
                        'cedula_identidad' => $row->cedula_identidad,
                        'numero_medidor' => $row->numero_medidor,
                        'telefono' => $row->telefono,
                        'email' => $row->email,
                        'facturas_pendientes' => array_fill(0, (int) $row->facturas_pendientes_count, null),
                        'subtotal_pendiente' => (float) $row->subtotal_pendiente,
                        'recargos_pendientes' => (float) $row->recargos_pendientes,
                        'total_pendiente' => (float) $row->total_pendiente,
                    ];
                })
                ->values();
        });

        $resumen = OperationalCache::rememberDomain('billing', $summaryKey, function () use ($search) {
            $row = DB::query()
                ->fromSub($this->paymentDebtorsQuery($search), 'deudores')
                ->selectRaw('
                    COUNT(*) as socios_con_pendientes,
                    COALESCE(SUM(recargos_pendientes), 0) as multas_pendientes,
                    COALESCE(SUM(total_pendiente), 0) as monto_total_pendiente
                ')
                ->first();

            return [
                'socios_con_pendientes' => (int) ($row->socios_con_pendientes ?? 0),
                'multas_pendientes' => round((float) ($row->multas_pendientes ?? 0), 2),
                'monto_total_pendiente' => round((float) ($row->monto_total_pendiente ?? 0), 2),
            ];
        });

        return [
            'socios' => $socios,
            'search' => $search,
            'resumen' => $resumen,
        ];
    }

    private function paymentDebtorsQuery(string $search)
    {
        $pagosPorFactura = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return DB::table('socios as s')
            ->join('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->join('facturas as f', function ($join) {
                $join->on('f.id_socio', '=', 's.id_socio')
                    ->whereIn('f.estado', ['pendiente', 'vencida', 'parcial']);
            })
            ->leftJoinSub($pagosPorFactura, 'cp', function ($join) {
                $join->on('cp.id_factura', '=', 'f.id_factura');
            })
            ->where('s.estado', '!=', 'inactivo')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('s.numero_socio', 'ilike', "%{$search}%")
                        ->orWhere('p.nombres', 'ilike', "%{$search}%")
                        ->orWhere('p.apellidos', 'ilike', "%{$search}%")
                        ->orWhere('p.cedula_identidad', 'ilike', "%{$search}%")
                        ->orWhere('m.numero_serie', 'ilike', "%{$search}%");
                });
            })
            ->groupBy('s.id_socio', 's.numero_socio', 'p.nombres', 'p.apellidos', 'p.cedula_identidad', 'p.telefono', 'p.email', 'm.numero_serie')
            ->havingRaw('SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)) > 0')
            ->select([
                's.id_socio',
                's.numero_socio',
                'p.cedula_identidad',
                'p.telefono',
                'p.email',
                'm.numero_serie as numero_medidor',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
                DB::raw('COUNT(DISTINCT f.id_factura) as facturas_pendientes_count'),
                DB::raw('ROUND(SUM(f.monto_consumo + f.cargo_fijo - f.descuentos), 2) as subtotal_pendiente'),
                DB::raw('ROUND(SUM(f.recargo_mora), 2) as recargos_pendientes'),
                DB::raw('ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as total_pendiente'),
            ]);
    }

    public function show(Request $request, Socio $socio)
    {
        $detail = $this->paymentDetailData((int) $socio->id_socio);

        $selectedSocio = $detail['selectedSocio'];
        $metodosPago = $detail['metodosPago'];
        $cobrosSocio = $detail['cobrosSocio'];

        $qrCuotaMonto = round($selectedSocio['subtotal_pendiente'], 2);
        $qrMoraMonto = round($selectedSocio['recargos_pendientes'], 2);

        return view('cobros.create', [
            'selectedSocio' => $selectedSocio,
            'metodosPago' => $metodosPago,
            'cobrosSocio' => $cobrosSocio,
            'qrCuotaSvg' => $this->paymentQrService->cachedSvg($selectedSocio, 'CUOTA', $qrCuotaMonto),
            'qrMoraSvg' => $this->paymentQrService->cachedSvg($selectedSocio, 'MORA', $qrMoraMonto),
            'qrCuotaMonto' => $qrCuotaMonto,
            'qrMoraMonto' => $qrMoraMonto,
        ]);
    }

    private function paymentDetailData(int $socioId): array
    {
        return OperationalCache::remember(
            "cobros:socio-detail:v2:{$socioId}",
            function () use ($socioId): array {
                $details = $this->buildPaymentDetails([$socioId]);

                return $details[$socioId] ?? abort(404);
            },
            now()->addHours(2)
        );
    }

    private function warmPaymentDetails(array $socioIds): void
    {
        $socioIds = collect($socioIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $missingIds = collect($socioIds)
            ->reject(fn (int $id) => Cache::has(OperationalCache::key("cobros:socio-detail:v2:{$id}")))
            ->values()
            ->all();

        if ($missingIds === []) {
            foreach ($socioIds as $socioId) {
                $detail = Cache::get(OperationalCache::key("cobros:socio-detail:v2:{$socioId}"));

                if (is_array($detail)) {
                    $this->warmQrForDetail($detail);
                }
            }

            return;
        }

        foreach ($this->buildPaymentDetails($missingIds) as $socioId => $detail) {
            Cache::put(
                OperationalCache::key("cobros:socio-detail:v2:{$socioId}"),
                $detail,
                now()->addHours(2)
            );

            $this->warmQrForDetail($detail);
        }
    }

    private function warmQrForDetail(array $detail): void
    {
        $selectedSocio = $detail['selectedSocio'] ?? null;

        if (! is_array($selectedSocio)) {
            return;
        }

        $this->paymentQrService->cachedSvg($selectedSocio, 'CUOTA', round((float) $selectedSocio['subtotal_pendiente'], 2));
        $this->paymentQrService->cachedSvg($selectedSocio, 'MORA', round((float) $selectedSocio['recargos_pendientes'], 2));
    }

    private function buildPaymentDetails(array $socioIds): array
    {
        $socioIds = collect($socioIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($socioIds->isEmpty()) {
            return [];
        }

        $socios = DB::table('socios as s')
            ->join('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->whereIn('s.id_socio', $socioIds->all())
            ->get([
                's.id_socio',
                's.numero_socio',
                'm.numero_serie as numero_medidor',
                'p.nombres',
                'p.apellidos',
                'p.cedula_identidad',
                'p.telefono',
                'p.email',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
            ])
            ->keyBy(fn ($row) => (int) $row->id_socio);

        if ($socios->isEmpty()) {
            return [];
        }

        $foundIds = $socios->keys()->map(fn ($id) => (int) $id)->all();
        $pagosPorFactura = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        $facturas = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->leftJoinSub($pagosPorFactura, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->whereIn('f.id_socio', $foundIds)
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('f.fecha_emision')
            ->orderBy('f.id_factura')
            ->get([
                'f.id_factura',
                'f.id_socio',
                'f.numero_factura',
                'f.fecha_emision',
                'f.estado',
                'f.monto_consumo',
                'f.cargo_fijo',
                'f.descuentos',
                'f.recargo_mora',
                'f.total',
                'pf.nombre as periodo_nombre',
                DB::raw('COALESCE(cp.pagado, 0) as pagado'),
            ])
            ->groupBy(fn ($row) => (int) $row->id_socio);

        $recentCobrosBase = DB::table('cobros as c')
            ->join('facturas as f', 'f.id_factura', '=', 'c.id_factura')
            ->leftJoin('metodos_pago as mp', 'mp.id_metodo_pago', '=', 'c.id_metodo_pago')
            ->whereIn('f.id_socio', $foundIds)
            ->select([
                'f.id_socio',
                'c.id_cobro',
                'c.fecha_cobro',
                'c.monto_pagado',
                'f.numero_factura',
                'mp.nombre as metodo_pago',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY f.id_socio ORDER BY c.fecha_cobro DESC, c.id_cobro DESC) as row_number'),
            ]);

        $recentCobros = DB::query()
            ->fromSub($recentCobrosBase, 'recent_cobros')
            ->where('row_number', '<=', 10)
            ->orderBy('id_socio')
            ->orderByDesc('fecha_cobro')
            ->orderByDesc('id_cobro')
            ->get()
            ->groupBy(fn ($row) => (int) $row->id_socio);

        $metodos = $this->cachedPaymentMethods();
        $details = [];

        foreach ($socios as $socioId => $socio) {
            $facturasSocio = collect($facturas->get($socioId, []))
                ->map(function ($factura) {
                    $pagado = (float) $factura->pagado;
                    $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
                    $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

                    return [
                        'id_factura' => (int) $factura->id_factura,
                        'numero_factura' => $factura->numero_factura,
                        'periodo' => $factura->periodo_nombre ?: 'Sin periodo',
                        'fecha_emision' => $factura->fecha_emision ? Carbon::parse($factura->fecha_emision)->format('d/m/Y') : '',
                        'estado' => $factura->estado,
                        'subtotal' => $subtotal,
                        'recargo_mora' => (float) $factura->recargo_mora,
                        'total' => (float) $factura->total,
                        'pagado' => $pagado,
                        'pendiente' => $pendiente,
                        'descripcion' => $this->facturaDescriptionFromRow($factura),
                    ];
                })
                ->filter(fn (array $factura) => $factura['pendiente'] > 0)
                ->values();

            $cobrosSocio = collect($recentCobros->get($socioId, []))
                ->map(fn ($cobro) => [
                    'numero_factura' => $cobro->numero_factura,
                    'fecha_cobro' => $cobro->fecha_cobro ? Carbon::parse($cobro->fecha_cobro) : null,
                    'metodo_pago' => $cobro->metodo_pago,
                    'monto_pagado' => (float) $cobro->monto_pagado,
                ])
                ->values();

            $details[(int) $socioId] = [
                'selectedSocio' => [
                    'id_socio' => (int) $socio->id_socio,
                    'nombre_completo' => $socio->nombre_completo ?: 'Sin socio',
                    'codigo_display' => $socio->numero_socio ?: 'SOC-'.str_pad((string) $socio->id_socio, 4, '0', STR_PAD_LEFT),
                    'cedula_identidad' => $socio->cedula_identidad,
                    'numero_medidor' => $socio->numero_medidor,
                    'telefono' => $socio->telefono,
                    'email' => $socio->email,
                    'facturas_pendientes' => $facturasSocio->all(),
                    'subtotal_pendiente' => round($facturasSocio->sum('subtotal'), 2),
                    'recargos_pendientes' => round($facturasSocio->sum('recargo_mora'), 2),
                    'total_pendiente' => round($facturasSocio->sum('pendiente'), 2),
                ],
                'metodosPago' => $metodos,
                'cobrosSocio' => $cobrosSocio,
            ];
        }

        return $details;
    }

    private function cachedPaymentMethods()
    {
        return Cache::remember('cobros:payment-methods:v2', now()->addDay(), function () {
            return MetodoPago::query()
                ->select([
                    'id_metodo_pago',
                    'nombre',
                    'estado',
                    'requiere_referencia',
                    'requiere_caja_abierta',
                    'es_online',
                    'requiere_conciliacion',
                    'origen_predeterminado',
                ])
                ->activos()
                ->orderByRaw("
                    CASE
                        WHEN LOWER(nombre) = 'efectivo' THEN 1
                        WHEN LOWER(nombre) = 'qr oficina' THEN 2
                        WHEN LOWER(nombre) = 'transferencia' THEN 3
                        WHEN LOWER(nombre) = 'qr online' THEN 4
                        ELSE 5
                    END
                ")
                ->orderBy('nombre')
                ->get();
        });
    }

    public function result()
    {
        $paymentResult = session('payment_result');

        if (! $paymentResult || empty($paymentResult['factura_ids'])) {
            return redirect()->route('secretaria.operaciones.index');
        }

        $facturas = Factura::query()
            ->with(['socio.persona', 'periodo', 'lectura.medidor'])
            ->whereIn('id_factura', $paymentResult['factura_ids'])
            ->orderByDesc('fecha_emision')
            ->get();

        return view('cobros.result', [
            'paymentResult' => $paymentResult,
            'facturas' => $facturas,
            'primaryFactura' => $facturas->first(),
        ]);
    }

    public function store(Request $request, Socio $socio)
    {
        $data = $request->validate([
            'id_metodo_pago' => ['required', 'integer'],
            'cantidad_pagada' => ['required', 'numeric', 'min:0.01'],
            'factura_ids' => ['required', 'array', 'min:1'],
            'factura_ids.*' => ['integer', 'distinct'],
            'comprobante' => ['nullable', 'string', 'max:100'],
        ]);

        $metodoPago = $this->paymentMethodById((int) $data['id_metodo_pago']);

        if (! $metodoPago) {
            return back()->withInput()->with('error', 'Selecciona un metodo de pago valido y activo.');
        }

        try {
            $empleado = $this->resolveEmpleado();
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No se encontro un empleado activo vinculado a tu usuario. Revisa el perfil del usuario antes de registrar pagos.');
        }

        if ($this->requiresOpenCashRegister($metodoPago) && ! $this->canRegisterSecretaryPayment($empleado)) {
            return back()->withInput()->with('error', 'Debes abrir la caja del dia antes de registrar pagos. Si ya fue cerrada, solicita revision al administrador.');
        }

        $fechaPago = now()->toDateString();

        try {
            $resultado = DB::transaction(function () use ($data, $empleado, $socio, $fechaPago, $metodoPago) {
                $cierreCajaId = $this->requiresOpenCashRegister($metodoPago)
                    ? $this->openCashRegisterId($empleado)
                    : null;

                $facturasBloqueadas = Factura::query()
                    ->where('facturas.id_socio', $socio->id_socio)
                    ->whereIn('facturas.estado', ['pendiente', 'parcial', 'vencida'])
                    ->orderByRaw('COALESCE(facturas.fecha_fin_cobro, facturas.fecha_emision) ASC')
                    ->orderBy('facturas.id_factura')
                    ->lockForUpdate()
                    ->get();

                $pagosPorFactura = DB::table('cobros')
                    ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                    ->whereIn('id_factura', $facturasBloqueadas->pluck('id_factura'))
                    ->groupBy('id_factura');

                $saldosPorFactura = DB::query()
                    ->from('facturas')
                    ->leftJoinSub($pagosPorFactura, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'facturas.id_factura'))
                    ->whereIn('facturas.id_factura', $facturasBloqueadas->pluck('id_factura'))
                    ->select('facturas.id_factura')
                    ->selectRaw('ROUND(CASE WHEN (facturas.total - COALESCE(cp.pagado, 0)) > 0 THEN (facturas.total - COALESCE(cp.pagado, 0)) ELSE 0 END, 2) as pendiente_calculado')
                    ->pluck('pendiente_calculado', 'id_factura');

                $facturasAbiertas = $facturasBloqueadas
                    ->map(fn (Factura $factura) => [
                        'factura' => $factura,
                        'pendiente' => (float) ($saldosPorFactura[$factura->id_factura] ?? 0),
                    ])
                    ->filter(fn (array $item) => $item['pendiente'] > 0)
                    ->values();

                $selectedIds = collect($data['factura_ids'])->map(fn ($id) => (int) $id)->sort()->values();
                $expectedIds = $facturasAbiertas->take($selectedIds->count())
                    ->map(fn (array $item) => (int) $item['factura']->id_factura)
                    ->sort()
                    ->values();

                if ($expectedIds->all() !== $selectedIds->all()) {
                    logger()->warning('billing.payment.invoice_order_mismatch', [
                        'id_socio' => $socio->id_socio,
                        'expected_ids' => $expectedIds->all(),
                        'selected_ids' => $selectedIds->all(),
                    ]);

                    throw new \RuntimeException('Debes pagar primero las facturas mas antiguas, sin saltar periodos pendientes.');
                }

                $facturasPendientes = $facturasAbiertas->take($selectedIds->count())->values();

                if ($facturasPendientes->isEmpty()) {
                    throw new \RuntimeException('Las facturas seleccionadas ya no tienen saldo pendiente.');
                }

                $totalSeleccionado = round($facturasPendientes->sum('pendiente'), 2);
                $cantidadPagada = round((float) $data['cantidad_pagada'], 2);

                $this->paymentAmountPolicy->validate(
                    $metodoPago,
                    $cantidadPagada,
                    $totalSeleccionado,
                    $data['comprobante'] ?? null
                );

                $cobros = collect();

                foreach ($facturasPendientes as $item) {
                    $factura = $item['factura'];
                    $montoPendiente = $item['pendiente'];

                    $cobro = Cobro::create($this->cobroPayload([
                        'fecha_cobro' => $fechaPago,
                        'monto_pagado' => $montoPendiente,
                        'monto_pendiente' => 0,
                        'estado' => 'completado',
                        'comprobante' => ($data['comprobante'] ?? null) ?: $this->buildComprobante($factura),
                        'id_factura' => $factura->id_factura,
                        'id_metodo_pago' => $metodoPago->id_metodo_pago,
                        'id_empleado' => $empleado->id_empleado,
                    ], [
                        'origen_pago' => $this->paymentOrigin($metodoPago),
                        'id_cierre_caja' => $cierreCajaId,
                        'referencia_externa' => $data['comprobante'] ?? null,
                        'estado_conciliacion' => $metodoPago->requiere_conciliacion ? 'pendiente' : 'no_aplica',
                        'confirmado_por' => $metodoPago->es_online ? 'portal' : 'oficina',
                        'confirmado_en' => now(),
                    ]));

                    $factura->update([
                        'estado' => 'pagada',
                        'fecha_pago' => $fechaPago,
                    ]);

                    HistorialPago::create([
                        'fecha_evento' => now(),
                        'tipo_evento' => 'pago_completo',
                        'descripcion' => 'Pago registrado desde la vista de cobros. Factura cancelada en su totalidad.',
                        'monto' => $montoPendiente,
                        'id_socio' => $factura->id_socio,
                        'id_factura' => $factura->id_factura,
                        'id_cobro' => $cobro->id_cobro,
                        'id_empleado' => $empleado->id_empleado,
                    ]);

                    $cobros->push($cobro);
                }

                return [
                    'cobros' => $cobros,
                    'metodo_pago' => $metodoPago,
                    'total_seleccionado' => $totalSeleccionado,
                    'cantidad_pagada' => $cantidadPagada,
                ];
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', $exception->getMessage() ?: 'No se pudo registrar el pago.');
        }

        OperationalCache::bumpDomain('billing');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        Cache::forget('dashboard.secretaria.stats');
        Cache::forget('dashboard.secretaria.payments.recent');
        Cache::forget('api.dashboard.secretaria');
        Cache::forget('secretaria:operations:cash:'.$empleado->id_empleado.':'.today()->toDateString());
        OperationalCache::forget("cobros:socio-detail:v2:{$socio->id_socio}");
        OperationalCache::forget("route-binding:App.Models.Socio:id_socio:{$socio->id_socio}");
        foreach ($resultado['cobros']->pluck('id_factura')->unique() as $facturaId) {
            OperationalCache::forget("factura:detail:{$facturaId}");
            OperationalCache::forget("route-binding:App.Models.Factura:id_factura:{$facturaId}");
        }

        return redirect()
            ->route('secretaria.cobros.result')
            ->with('payment_result', [
                'socio_id' => $socio->id_socio,
                'socio_nombre' => $socio->persona?->nombre_completo ?? 'Socio',
                'factura_ids' => $resultado['cobros']->pluck('id_factura')->values()->all(),
                'cobro_ids' => $resultado['cobros']->pluck('id_cobro')->values()->all(),
                'total_pagado' => (float) $resultado['total_seleccionado'],
                'cantidad_pagada' => (float) $resultado['cantidad_pagada'],
                'cambio' => max(0, round((float) $resultado['cantidad_pagada'] - (float) $resultado['total_seleccionado'], 2)),
                'metodo_pago' => $resultado['metodo_pago']->nombre,
                'fecha_pago' => $fechaPago,
            ])
            ->with('success', 'Pago registrado correctamente. Ya puedes imprimir o descargar el recibo sin volver a facturacion.');
    }

    private function mapSocioCobroData(Socio $socio): array
    {
        $facturas = $socio->facturasPendientes
            ->sortBy('fecha_emision')
            ->map(function (Factura $factura) {
                $pagado = (float) $factura->cobros
                    ->where('estado', '!=', 'anulado')
                    ->sum('monto_pagado');
                $pendiente = round(max(0, (float) $factura->total - $pagado), 2);
                $subtotal = round((float) $factura->monto_consumo + (float) $factura->cargo_fijo - (float) $factura->descuentos, 2);

                return [
                    'id_factura' => $factura->id_factura,
                    'numero_factura' => $factura->numero_factura,
                    'periodo' => $factura->periodo?->nombre ?? 'Sin periodo',
                    'fecha_emision' => optional($factura->fecha_emision)->format('d/m/Y'),
                    'estado' => $factura->estado,
                    'subtotal' => $subtotal,
                    'recargo_mora' => (float) $factura->recargo_mora,
                    'total' => (float) $factura->total,
                    'pagado' => $pagado,
                    'pendiente' => $pendiente,
                    'descripcion' => $this->facturaDescription($factura),
                ];
            })
            ->filter(fn (array $factura) => $factura['pendiente'] > 0)
            ->values();

        return [
            'id_socio' => $socio->id_socio,
            'nombre_completo' => $socio->persona?->nombre_completo ?? 'Sin socio',
            'codigo_display' => $socio->codigo_display,
            'cedula_identidad' => $socio->persona?->cedula_identidad,
            'telefono' => $socio->persona?->telefono,
            'email' => $socio->persona?->email,
            'facturas_pendientes' => $facturas->all(),
            'subtotal_pendiente' => round($facturas->sum('subtotal'), 2),
            'recargos_pendientes' => round($facturas->sum('recargo_mora'), 2),
            'total_pendiente' => round($facturas->sum('pendiente'), 2),
        ];
    }

    private function facturaDescription(Factura $factura): string
    {
        $partes = ['Consumo de agua'];

        if ((float) $factura->recargo_mora > 0) {
            $partes[] = 'incluye mora';
        }

        if ($factura->estado === 'vencida') {
            $partes[] = 'pago retrasado';
        }

        return ucfirst(implode(', ', $partes));
    }

    private function facturaDescriptionFromRow(object $factura): string
    {
        $partes = ['Consumo de agua'];

        if ((float) $factura->recargo_mora > 0) {
            $partes[] = 'incluye mora';
        }

        if ($factura->estado === 'vencida') {
            $partes[] = 'pago retrasado';
        }

        return ucfirst(implode(', ', $partes));
    }

    private function pendingAmount(Factura $factura): float
    {
        $pagado = (float) ($factura->relationLoaded('cobros')
            ? $factura->cobros->where('estado', '!=', 'anulado')->sum('monto_pagado')
            : $factura->cobros()->where('estado', '!=', 'anulado')->sum('monto_pagado'));

        return round(max(0, (float) $factura->total - $pagado), 2);
    }

    private function buildComprobante(Factura $factura): string
    {
        return 'COB-'.$factura->numero_factura.'-'.now()->format('YmdHis');
    }

    private function resolveEmpleado(): Empleado
    {
        return CurrentEmployee::resolve();
    }

    private function canRegisterSecretaryPayment(Empleado $empleado): bool
    {
        $user = Auth::user();

        if (! $user?->hasRole('secretaria') || $user->hasRole('administrador')) {
            return true;
        }

        return Cache::remember(
            'secretaria:can-register-payment:'.$empleado->id_empleado.':'.today()->toDateString(),
            now()->endOfDay(),
            fn () => CierreCaja::query()
                ->where('id_empleado', $empleado->id_empleado)
                ->whereDate('fecha_caja', today())
                ->where('estado', 'abierta')
                ->exists()
        );
    }

    private function paymentMethodById(int $id): ?MetodoPago
    {
        return $this->cachedPaymentMethods()
            ->firstWhere('id_metodo_pago', $id);
    }

    private function requiresOpenCashRegister(MetodoPago $metodoPago): bool
    {
        return (bool) ($metodoPago->requiere_caja_abierta ?? true);
    }

    private function paymentOrigin(MetodoPago $metodoPago): string
    {
        $origin = trim((string) ($metodoPago->origen_predeterminado ?? ''));

        if ($origin !== '') {
            return $origin;
        }

        return $metodoPago->es_online ? 'online' : 'caja';
    }

    private function openCashRegisterId(Empleado $empleado): ?int
    {
        return CierreCaja::query()
            ->where('id_empleado', $empleado->id_empleado)
            ->whereDate('fecha_caja', today())
            ->where('estado', 'abierta')
            ->value('id_cierre_caja');
    }

    private function cobroPayload(array $base, array $operational): array
    {
        foreach ($operational as $column => $value) {
            if ($this->cobrosHasColumn($column)) {
                $base[$column] = $value;
            }
        }

        return $base;
    }

    private function cobrosHasColumn(string $column): bool
    {
        return Cache::remember("schema:cobros:{$column}", now()->addHours(12), fn () => Schema::hasColumn('cobros', $column));
    }

}
