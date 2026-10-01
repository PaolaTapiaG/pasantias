<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Comunicado;
use App\Models\IncidenciaTecnica;
use App\Models\OperacionSistema;
use App\Models\OrdenTecnica;
use App\Support\CurrentEmployee;
use App\Support\OperationalCache;
use App\Services\CashRegisterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SecretariaOperacionController extends Controller
{
    public function __construct(private CashRegisterQuery $cashRegisterQuery)
    {
    }

    public function index(Request $request): View
    {
        $employee = CurrentEmployee::resolve();
        $cash = $this->cashRegisterQuery->current((int) $employee->id_empleado);
        $paymentOverview = $this->paymentOverview(trim((string) $request->query('buscar_pago', '')));

        return view('secretaria.operaciones', [
            'employee' => $employee,
            'cash' => $cash,
            'cashSummary' => $this->cashRegisterQuery->summary((int) $employee->id_empleado),
            ...$paymentOverview,
            'requests' => $this->recentRequests(),
            'technicalOrders' => $this->recentTechnicalOrders(),
            'communications' => $this->recentCommunications(),
            'socios' => $this->sociosCatalog(),
            'technicians' => $this->technicianCatalog(),
            'isAdmin' => Auth::user()?->hasRole('administrador') ?? false,
        ]);
    }

    public function warmIndexCache(): void
    {
        $this->recentRequests();
        $this->recentTechnicalOrders();
        $this->recentCommunications();
        $this->sociosCatalog();
        $this->technicianCatalog();
        $this->paymentOverview('');

        $employeeIds = DB::table('empleados as e')
            ->join('roles as r', 'r.id_rol', '=', 'e.id_rol')
            ->where('e.estado', 'activo')
            ->whereIn(DB::raw('LOWER(r.nombre)'), ['admin', 'administrador', 'secretaria'])
            ->pluck('e.id_empleado');

        foreach ($employeeIds as $employeeId) {
            $this->cashRegisterQuery->current((int) $employeeId);
            $this->cashRegisterQuery->summary((int) $employeeId);
        }
    }

    public function openCash(): RedirectResponse
    {
        $employeeId = CurrentEmployee::id();
        $cash = CierreCaja::query()->firstOrCreate(
            [
                'id_empleado' => $employeeId,
                'fecha_caja' => today()->toDateString(),
            ],
            [
                'estado' => 'abierta',
                'abierta_en' => now(),
            ]
        );

        if ($cash->estado !== 'abierta') {
            return back()->with('error', 'La caja de hoy ya fue cerrada y no puede volver a abrirse sin revision administrativa.');
        }

        $this->recordOperation('apertura_caja', 'Caja abierta para el turno diario.', $employeeId);
        $this->flushCaches();

        return back()->with('success', 'Caja abierta. Ya puedes registrar cobros presenciales.');
    }

    public function closeCash(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);
        $employeeId = CurrentEmployee::id();
        $cash = CierreCaja::query()
            ->where('id_empleado', $employeeId)
            ->whereDate('fecha_caja', today())
            ->first();

        if (! $cash || $cash->estado !== 'abierta') {
            return back()->with('error', 'No existe una caja abierta para cerrar.');
        }

        $summary = $this->cashRegisterQuery->summary($employeeId, false);
        $cash->update([
            'estado' => 'cerrada',
            'cantidad_cobros' => $summary['cantidad_cobros'],
            'total_efectivo' => $summary['efectivo'],
            'total_qr' => $summary['qr'],
            'total_transferencia' => $summary['transferencia'],
            'total_general' => $summary['total'],
            'observaciones' => $data['observaciones'] ?? null,
            'cerrada_en' => now(),
        ]);

        $this->recordOperation('cierre_caja', 'Caja cerrada por Bs '.number_format($summary['total'], 2).'.', $employeeId);
        $this->flushCaches();

        return back()->with('success', 'Caja cerrada correctamente. Los pagos posteriores requieren una nueva jornada o revision administrativa.');
    }

    public function reviewCash(CierreCaja $cierreCaja): RedirectResponse
    {
        abort_unless(Auth::user()?->hasRole('administrador'), 403);

        $cierreCaja->update([
            'estado' => 'revisada',
            'revisado_por' => CurrentEmployee::id(),
            'revisada_en' => now(),
        ]);
        $this->flushCaches();

        return back()->with('success', 'El cierre de caja fue revisado por administracion.');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['reclamo_consumo', 'fuga', 'corte', 'lectura_incorrecta', 'inspeccion', 'cambio_titular', 'otro'])],
            'prioridad' => ['required', Rule::in(['alta', 'media', 'baja'])],
            'zona' => ['required', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:1500'],
            'id_socio' => ['nullable', 'exists:socios,id_socio'],
            'derivar' => ['nullable', 'boolean'],
            'id_tecnico' => ['nullable', 'exists:empleados,id_empleado'],
            'fecha_programada' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $employeeId = CurrentEmployee::id();
        $assignedId = $request->boolean('derivar') && ! empty($data['id_tecnico'])
            ? (int) $data['id_tecnico']
            : $employeeId;

        $incident = IncidenciaTecnica::query()->create([
            'tipo' => $data['tipo'],
            'prioridad' => $data['prioridad'],
            'estado' => $request->boolean('derivar') ? 'en_proceso' : 'abierta',
            'zona' => $data['zona'],
            'fecha_reporte' => now(),
            'descripcion' => '[Atencion secretaria] '.$data['descripcion'],
            'id_socio' => $data['id_socio'] ?? null,
            'id_empleado' => $assignedId,
        ]);

        if ($request->boolean('derivar') && ! empty($data['id_tecnico'])) {
            OrdenTecnica::query()->create([
                'tipo' => 'inspeccion',
                'estado' => 'pendiente',
                'prioridad' => $data['prioridad'],
                'fecha_programada' => $data['fecha_programada'] ?? null,
                'zona' => $data['zona'],
                'referencia' => 'Solicitud secretaria #'.$incident->id_incidencia,
                'descripcion' => $data['descripcion'],
                'id_socio' => $data['id_socio'] ?? null,
                'id_empleado' => (int) $data['id_tecnico'],
            ]);
        }

        $this->recordOperation('solicitud_cliente', 'Solicitud #'.$incident->id_incidencia.' registrada: '.$data['descripcion'], $employeeId, $data['zona']);
        $this->flushCaches();

        return back()->with('success', $request->boolean('derivar')
            ? 'Solicitud registrada y derivada al tecnico.'
            : 'Solicitud o reclamo registrado para seguimiento.');
    }

    public function updateRequest(Request $request, IncidenciaTecnica $incidencia): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['abierta', 'en_proceso', 'cerrada'])],
        ]);

        $incidencia->update(['estado' => $data['estado']]);
        $this->flushCaches();

        return back()->with('success', 'Estado de la solicitud actualizado.');
    }

    public function storeCommunication(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'contenido' => ['required', 'string', 'max:3000'],
            'importante' => ['nullable', 'boolean'],
            'publicar_desde' => ['nullable', 'date'],
            'publicar_hasta' => ['nullable', 'date', 'after_or_equal:publicar_desde'],
        ]);

        Comunicado::query()->create([
            ...$data,
            'estado' => 'pendiente_aprobacion',
            'importante' => $request->boolean('importante'),
            'id_empleado' => CurrentEmployee::id(),
        ]);
        $this->flushCaches();

        return back()->with('success', 'Comunicado guardado y enviado para aprobacion administrativa.');
    }

    public function updateCommunication(Request $request, Comunicado $comunicado): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['borrador', 'pendiente_aprobacion', 'publicado', 'rechazado', 'archivado'])],
        ]);

        if (in_array($data['estado'], ['publicado', 'rechazado', 'archivado'], true)) {
            abort_unless(Auth::user()?->hasRole('administrador'), 403);
        }

        $comunicado->update([
            'estado' => $data['estado'],
            'aprobado_por' => $data['estado'] === 'publicado' ? CurrentEmployee::id() : $comunicado->aprobado_por,
            'aprobado_en' => $data['estado'] === 'publicado' ? now() : $comunicado->aprobado_en,
        ]);
        $this->flushCaches();

        return back()->with('success', 'Estado del comunicado actualizado.');
    }

    private function paymentOverview(string $search): array
    {
        $search = trim($search);

        if ($search !== '' && strlen($search) < 2) {
            return [
                'cajaSearch' => $search,
                'cajaSearchTooShort' => true,
                'deudoresCaja' => collect(),
                'facturasPendientesCaja' => collect(),
                'cajaResumen' => [
                    'socios_con_pendientes' => 0,
                    'facturas_pendientes' => 0,
                    'total_pendiente' => 0.0,
                ],
            ];
        }

        $cacheKey = 'secretaria.cash-attention.'.md5($search);

        return OperationalCache::rememberDomain('billing', 'secretaria.cash-attention.'.$cacheKey, function () use ($search) {
            $paymentsByInvoice = DB::table('cobros')
                ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                ->groupBy('id_factura');

            $debtorsBase = $this->cashDebtorsQuery($paymentsByInvoice, $search);

            $summary = DB::query()
                ->fromSub(clone $debtorsBase, 'deudores')
                ->selectRaw('COUNT(*) as socios_con_pendientes')
                ->selectRaw('COALESCE(SUM(facturas_pendientes_count), 0) as facturas_pendientes')
                ->selectRaw('COALESCE(SUM(total_pendiente), 0) as total_pendiente')
                ->first();

            $debtors = (clone $debtorsBase)
                ->orderByRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) asc")
                ->limit($search !== '' ? 25 : 12)
                ->get();

            $balance = 'GREATEST(f.total - COALESCE(cp.pagado, 0), 0)';
            $invoices = DB::table('facturas as f')
                ->join('socios as s', 's.id_socio', '=', 'f.id_socio')
                ->join('personas as p', 'p.id_persona', '=', 's.id_persona')
                ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
                ->leftJoin('medidores as m', function ($join) {
                    $join->on('m.id_socio', '=', 's.id_socio')
                        ->where('m.estado', '=', 'activo');
                })
                ->leftJoinSub($paymentsByInvoice, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
                ->where('s.estado', '<>', 'inactivo')
                ->whereIn('f.estado', ['pendiente', 'vencida', 'parcial'])
                ->when($search !== '', fn ($query) => $this->applyPaymentSearch($query, $search))
                ->whereRaw("{$balance} > 0")
                ->orderByRaw('COALESCE(f.fecha_fin_cobro, f.fecha_emision) ASC')
                ->orderBy('f.id_factura')
                ->limit(20)
                ->get([
                    'f.id_factura',
                    'f.numero_factura',
                    'f.fecha_emision',
                    'f.fecha_fin_cobro',
                    'f.estado',
                    'f.id_socio',
                    's.numero_socio',
                    'm.numero_serie as numero_medidor',
                    'pf.nombre as periodo',
                    DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre"),
                    DB::raw("ROUND({$balance}, 2) as saldo"),
                ]);

            return [
                'cajaSearch' => $search,
                'cajaSearchTooShort' => false,
                'deudoresCaja' => $debtors,
                'facturasPendientesCaja' => $invoices,
                'cajaResumen' => [
                    'socios_con_pendientes' => (int) ($summary->socios_con_pendientes ?? 0),
                    'facturas_pendientes' => (int) ($summary->facturas_pendientes ?? 0),
                    'total_pendiente' => round((float) ($summary->total_pendiente ?? 0), 2),
                ],
            ];
        });
    }

    private function cashDebtorsQuery($paymentsByInvoice, string $search)
    {
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
            ->leftJoinSub($paymentsByInvoice, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('s.estado', '<>', 'inactivo')
            ->when($search !== '', fn ($query) => $this->applyPaymentSearch($query, $search))
            ->groupBy('s.id_socio', 's.numero_socio', 'p.nombres', 'p.apellidos', 'p.cedula_identidad', 'm.numero_serie')
            ->havingRaw('SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)) > 0')
            ->select([
                's.id_socio',
                's.numero_socio',
                'p.cedula_identidad',
                'm.numero_serie as numero_medidor',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
                DB::raw('COUNT(DISTINCT f.id_factura) as facturas_pendientes_count'),
                DB::raw('ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as total_pendiente'),
            ]);
    }

    private function applyPaymentSearch($query, string $search): void
    {
        $query->where(function ($builder) use ($search) {
            $builder->where('s.numero_socio', 'ilike', "%{$search}%")
                ->orWhere('p.nombres', 'ilike', "%{$search}%")
                ->orWhere('p.apellidos', 'ilike', "%{$search}%")
                ->orWhere('p.cedula_identidad', 'ilike', "%{$search}%")
                ->orWhere('m.numero_serie', 'ilike', "%{$search}%");
        });
    }

    private function recentRequests()
    {
        return OperationalCache::rememberDomain('operations', 'secretaria.operations.requests', fn () => DB::table('incidencias_tecnicas as i')
            ->leftJoin('socios as s', 's.id_socio', '=', 'i.id_socio')
            ->leftJoin('personas as sp', 'sp.id_persona', '=', 's.id_persona')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'i.id_empleado')
            ->leftJoin('personas as ep', 'ep.id_persona', '=', 'e.id_persona')
            ->select([
                'i.id_incidencia',
                'i.tipo',
                'i.prioridad',
                'i.estado',
                'i.zona',
                'i.fecha_reporte',
                'i.descripcion',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(sp.nombres, '') || ' ' || COALESCE(sp.apellidos, '')) as socio_nombre"),
                DB::raw("TRIM(COALESCE(ep.nombres, '') || ' ' || COALESCE(ep.apellidos, '')) as responsable_nombre"),
            ])
            ->orderByRaw("CASE i.estado WHEN 'abierta' THEN 0 WHEN 'en_proceso' THEN 1 ELSE 2 END")
            ->orderByDesc('i.fecha_reporte')
            ->limit(20)
            ->get());
    }

    private function recentTechnicalOrders()
    {
        return OperationalCache::rememberDomain('operations', 'secretaria.operations.technical-orders', fn () => DB::table('ordenes_tecnicas as o')
            ->leftJoin('socios as s', 's.id_socio', '=', 'o.id_socio')
            ->leftJoin('personas as sp', 'sp.id_persona', '=', 's.id_persona')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'o.id_empleado')
            ->leftJoin('personas as ep', 'ep.id_persona', '=', 'e.id_persona')
            ->select([
                'o.id_orden',
                'o.tipo',
                'o.estado',
                'o.prioridad',
                'o.fecha_programada',
                'o.zona',
                'o.referencia',
                's.numero_socio',
                DB::raw("TRIM(COALESCE(sp.nombres, '') || ' ' || COALESCE(sp.apellidos, '')) as socio_nombre"),
                DB::raw("TRIM(COALESCE(ep.nombres, '') || ' ' || COALESCE(ep.apellidos, '')) as tecnico_nombre"),
            ])
            ->orderByDesc('o.created_at')
            ->limit(15)
            ->get());
    }

    private function recentCommunications()
    {
        return OperationalCache::rememberDomain('operations', 'secretaria.operations.communications', fn () => Comunicado::query()
            ->with('autor.persona')
            ->latest('created_at')
            ->limit(15)
            ->get());
    }

    private function sociosCatalog()
    {
        return OperationalCache::rememberDomain('operations', 'secretaria.operations.socios', fn () => DB::table('socios as s')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->where('s.estado', '<>', 'inactivo')
            ->select([
                's.id_socio',
                's.numero_socio',
                's.direccion',
                'sec.nombre as zona',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre"),
            ])
            ->orderBy('p.apellidos')
            ->limit(150)
            ->get());
    }

    private function technicianCatalog()
    {
        return OperationalCache::rememberDomain('operations', 'secretaria.operations.technicians', fn () => DB::table('empleados as e')
            ->join('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->join('roles as r', 'r.id_rol', '=', 'e.id_rol')
            ->where('e.estado', 'activo')
            ->whereRaw("LOWER(r.nombre) = 'tecnico'")
            ->select([
                'e.id_empleado',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre"),
            ])
            ->orderBy('p.apellidos')
            ->get());
    }

    private function recordOperation(string $type, string $description, int $employeeId, string $zone = 'Oficina'): void
    {
        OperacionSistema::query()->create([
            'fecha_operacion' => today(),
            'tipo_operacion' => $type,
            'zona' => $zone,
            'estado' => 'registrada',
            'horario' => now()->format('H:i'),
            'descripcion' => $description,
            'id_empleado' => $employeeId,
        ]);
    }

    private function flushCaches(): void
    {
        OperationalCache::bumpDomain('operations');
        OperationalCache::bumpDomain('billing');
        $employeeId = CurrentEmployee::id();

        foreach ([
            'secretaria:operations:cash:'.$employeeId.':'.today()->toDateString(),
            'secretaria:operations:current-cash:'.$employeeId.':'.today()->toDateString(),
            'secretaria:can-register-payment:'.$employeeId.':'.today()->toDateString(),
            'secretaria:operations:requests',
            'secretaria:operations:technical-orders',
            'secretaria:operations:communications',
            'dashboard.secretaria.stats',
            'api.dashboard.secretaria',
        ] as $key) {
            Cache::forget($key);
        }

        OperationalCache::forget('orders:reconexion:summary');
        OperationalCache::forget('orders:instalacion:summary');
        OperationalCache::forget('reconexion:open-socios');
        OperationalCache::forget('api-dashboard-tecnico');

        if (! app()->runningInConsole()) {
            app()->terminating(function () {
                try {
                    $this->warmIndexCache();
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        }
    }
}
