<?php

namespace App\Services;

use App\Http\Controllers\LecturaController;
use App\Http\Controllers\MedidorController;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\IncidenciaTecnica;
use App\Models\Lectura;
use App\Models\Medidor;
use App\Models\MedidorAnomalia;
use App\Models\Notificacion;
use App\Models\OperacionSistema;
use App\Models\OrdenTecnica;
use App\Models\ReporteTecnico;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\OperationalCache;
use App\Support\PrivateMedia;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TechnicalPanelService
{
    public function consumo(): View
    {
        return view('tecnico.consumo', [
            'moduleTitle' => 'Registro de consumo',
            'moduleDescription' => 'Registra lecturas de campo con sugerencia de lectura anterior y trazabilidad por medidor.',
            'moduleStats' => $this->consumptionStats(),
            'medidoresDisponibles' => $this->optimizedConsumptionCatalog(),
            'recentReadings' => $this->recentReadings(),
        ]);
    }

    public function anomalias(): View
    {
        $data = $this->anomalyModuleData();

        return view('tecnico.anomalias', [
            'moduleTitle' => 'Anomalias de medidores',
            'moduleDescription' => 'Reporta daños, manipulación y lecturas atípicas con seguimiento operativo.',
            'moduleStats' => $data['moduleStats'],
            'medidoresDisponibles' => $this->optimizedConsumptionCatalog(),
            'recentAnomalias' => $data['recentAnomalias'],
        ]);
    }

    public function storeAnomalia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_medidor' => ['required', 'exists:medidores,id_medidor'],
            'tipo' => ['required', Rule::in(['medidor_danado', 'manipulado', 'lectura_inconsistente', 'fuga_visible'])],
            'fecha_reporte' => ['required', 'date', 'before_or_equal:today'],
            'prioridad' => ['required', Rule::in(['alta', 'media', 'baja'])],
            'descripcion' => ['required', 'string', 'max:1000'],
            'evidencia' => ['nullable', 'image', 'max:4096'],
        ]);

        $anomalia = MedidorAnomalia::create($data + [
            'estado' => 'pendiente',
            'id_empleado' => $this->resolveEmpleadoId(),
            'evidencia_path' => $this->storeEvidence($request, 'evidencia', 'tecnico/anomalias'),
        ]);
        $facturaMulta = $this->attachAnomalyPenalty($anomalia->loadMissing('medidor'));

        $this->invalidateOperationalCaches();

        $message = 'Anomalia registrada correctamente.';
        if ($facturaMulta) {
            $message .= ' La multa fue cargada automaticamente a la factura '.$facturaMulta->numero_factura.'.';
        }

        return redirect()->route('tecnico.anomalias.index')->with('success', $message);
    }

    public function cortes(): View
    {
        return $this->orderModuleView(
            'corte',
            'tecnico.cortes',
            'Cortes de servicio',
            'Programa cortes por mora o intervención técnica y deja trazabilidad del trabajo de campo.'
        );
    }

    public function storeCorte(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'corte', 'tecnico.cortes.index', 'Corte registrado correctamente.');
    }

    public function reconexiones(): View
    {
        return $this->orderModuleView(
            'reconexion',
            'tecnico.reconexiones',
            'Reconexiones',
            'Registra reconexiones luego de validación de pago o autorización operativa.'
        );
    }

    public function storeReconexion(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'reconexion', 'tecnico.reconexiones.index', 'Reconexión registrada correctamente.');
    }

    public function instalaciones(): View
    {
        return $this->orderModuleView(
            'instalacion',
            'tecnico.instalaciones',
            'Instalaciones nuevas',
            'Controla nuevas conexiones, checklist de alta y ubicación operativa por zona.'
        );
    }

    public function storeInstalacion(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'instalacion', 'tecnico.instalaciones.index', 'Instalación registrada correctamente.');
    }

    public function mantenimiento(): View
    {
        [$stats, $orders] = $this->orderSummary('mantenimiento');
        $maintenanceMap = $this->maintenanceMapData();

        return view('tecnico.mantenimiento', [
            'moduleTitle' => 'Mantenimiento de red',
            'moduleDescription' => 'Administra reparaciones, inspecciones y puntos críticos sobre un mapa operativo simple.',
            'moduleStats' => $stats,
            'recentOrders' => $orders,
            'socios' => $this->socioCatalog(),
            'zonas' => $this->zoneCatalog(),
            'mapPoints' => $maintenanceMap['mapPoints'],
            'zoneMap' => $maintenanceMap['zoneMap'],
        ]);
    }

    public function storeMantenimiento(Request $request): RedirectResponse
    {
        return $this->storeOrder($request, 'mantenimiento', 'tecnico.mantenimiento.index', 'Orden de mantenimiento registrada correctamente.');
    }

    public function operacion(): View
    {
        $data = $this->operationModuleData();

        return view('tecnico.operacion', [
            'moduleTitle' => 'Operacion del sistema',
            'moduleDescription' => 'Registra maniobras de bombeo, distribución por zonas y ajustes operativos.',
            'moduleStats' => $data['moduleStats'],
            'recentOperations' => $data['recentOperations'],
            'zonas' => $this->zoneCatalog(),
        ]);
    }

    public function storeOperacion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo_operacion' => ['required', Rule::in(['bomba', 'distribucion', 'valvula', 'abastecimiento', 'inspeccion'])],
            'zona' => ['required', 'string', 'max:120'],
            'fecha_operacion' => ['required', 'date', 'before_or_equal:today'],
            'estado' => ['required', Rule::in(['operativa', 'ajustada', 'alerta', 'mantenimiento'])],
            'horario' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ]);

        OperacionSistema::create($data + ['id_empleado' => $this->resolveEmpleadoId()]);
        $this->invalidateOperationalCaches();

        return redirect()->route('tecnico.operacion.index')->with('success', 'Operación registrada correctamente.');
    }

    public function reportes(): View
    {
        $data = $this->technicalReportModuleData();

        return view('tecnico.reportes', [
            'moduleTitle' => 'Reportes tecnicos',
            'moduleDescription' => 'Centraliza reportes de campo, observaciones de red y recomendaciones operativas.',
            'moduleStats' => $data['moduleStats'],
            'recentReports' => $data['recentReports'],
        ]);
    }

    public function storeReporte(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'categoria' => ['required', Rule::in(['rotura', 'baja_presion', 'bombas', 'mantenimiento', 'incidencia'])],
            'fecha_reporte' => ['required', 'date', 'before_or_equal:today'],
            'estado' => ['required', Rule::in(['borrador', 'emitido', 'cerrado'])],
            'resumen' => ['required', 'string', 'max:1500'],
            'recomendaciones' => ['nullable', 'string', 'max:1500'],
        ]);

        ReporteTecnico::create($data + ['id_empleado' => $this->resolveEmpleadoId()]);
        $this->invalidateOperationalCaches();

        return redirect()->route('tecnico.reportes-tecnicos.index')->with('success', 'Reporte técnico guardado correctamente.');
    }

    public function incidencias(): View
    {
        $data = $this->incidentModuleData();

        return view('tecnico.incidencias', [
            'moduleTitle' => 'Gestion de incidencias',
            'moduleDescription' => 'Registra eventos críticos de red, fugas mayores y afectaciones por zona.',
            'moduleStats' => $data['moduleStats'],
            'recentIncidencias' => $data['recentIncidencias'],
            'socios' => $this->socioCatalog(),
            'zonas' => $this->zoneCatalog(),
        ]);
    }

    public function medidorCatalog(Request $request): JsonResponse
    {
        $items = $this->meterCatalogRows(trim((string) $request->query('q', '')), 25)
            ->map(fn ($medidor) => [
                'value' => $medidor->id_medidor,
                'label' => $medidor->numero_serie.' · '.$medidor->codigo_usuario.' · '.$medidor->socio_nombre,
                'anterior' => $medidor->lectura_sugerida,
                'latitud' => $medidor->latitud,
                'longitud' => $medidor->longitud,
                'meta' => $medidor->codigo_usuario.' · '.$medidor->socio_nombre.' · '.$medidor->zona,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function socioCatalogSearch(Request $request): JsonResponse
    {
        $signals = $this->billingSignals();
        $items = $this->socioCatalogRows(trim((string) $request->query('q', '')), 25)
            ->map(function ($socio) use ($signals) {
                $signal = $signals->get($socio->id_socio);

                return [
                    'value' => $socio->id_socio,
                    'label' => $socio->codigo_display.' · '.$socio->persona?->nombre_completo.' · '.$socio->sector?->nombre,
                    'zona' => $socio->sector?->nombre,
                    'direccion' => $socio->direccion,
                    'latitud' => $socio->latitud,
                    'longitud' => $socio->longitud,
                    'referencia_corte' => $signal && $signal['total_pendiente'] > 0
                        ? 'Deuda pendiente Bs '.number_format($signal['total_pendiente'], 2).' · '.$signal['facturas_abiertas'].' factura(s) abiertas'
                        : '',
                    'referencia_reconexion' => $signal && $signal['ultima_fecha_pago']
                        ? 'Pago validado el '.Carbon::parse($signal['ultima_fecha_pago'])->format('d/m/Y').($signal['ultimo_cobro_id'] ? ' · Cobro #'.$signal['ultimo_cobro_id'] : '')
                        : '',
                    'resumen' => $signal
                        ? 'Pendiente Bs '.number_format($signal['total_pendiente'], 2).' · vencidas '.$signal['facturas_vencidas']
                        : 'Sin datos comerciales',
                    'ultimo_cobro_id' => $signal['ultimo_cobro_id'] ?? null,
                    'ultima_fecha_pago' => $signal['ultima_fecha_pago'] ?? null,
                ];
            })
            ->values();

        return response()->json(['items' => $items]);
    }

    public function storeIncidencia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['fuga_grande', 'corte_general', 'baja_presion', 'problema_bomba'])],
            'zona' => ['required', 'string', 'max:120'],
            'fecha_reporte' => ['required', 'date'],
            'prioridad' => ['required', Rule::in(['alta', 'media', 'baja'])],
            'estado' => ['required', Rule::in(['abierta', 'en_proceso', 'cerrada'])],
            'descripcion' => ['required', 'string', 'max:1200'],
            'coord_x' => ['nullable', 'numeric', 'between:-90,90'],
            'coord_y' => ['nullable', 'numeric', 'between:-180,180'],
            'id_socio' => ['nullable', 'exists:socios,id_socio'],
            'evidencia' => ['nullable', 'image', 'max:4096'],
        ]);

        $incidencia = IncidenciaTecnica::create($data + [
            'id_empleado' => $this->resolveEmpleadoId(),
            'evidencia_path' => $this->storeEvidence($request, 'evidencia', 'tecnico/incidencias'),
        ]);

        if ($request->filled('gasto_concepto') || $request->filled('gasto_monto') || $request->filled('materiales_utilizados')) {
            $this->createIncidentExpense($request, $incidencia);
        }

        $this->invalidateOperationalCaches();

        return redirect()->route('tecnico.incidencias.index')->with('success', 'Incidencia registrada correctamente.');
    }

    public function approveReconexion(OrdenTecnica $orden): RedirectResponse
    {
        abort_unless($orden->tipo === 'reconexion', 404);

        $orden->update([
            'estado' => 'en_proceso',
            'referencia' => trim(($orden->referencia ? $orden->referencia.' · ' : '').'Aprobada '.now()->format('d/m/Y H:i')),
        ]);

        $this->invalidateOperationalCaches();

        return back()->with('success', 'La reconexion quedo aprobada para ejecucion tecnica.');
    }

    public function warmIndexCache(): void
    {
        $this->consumptionStats();
        $this->recentReadings();
        $this->optimizedConsumptionCatalog();
        $this->anomalyModuleData();
        $this->socioCatalog();
        $this->zoneCatalog();
        $this->billingSignals();

        foreach (['corte', 'reconexion', 'instalacion', 'mantenimiento'] as $type) {
            $this->orderSummary($type);
        }

        $this->corteCandidates();
        $this->reconnectionCandidates();
        $this->maintenanceMapData();
        $this->operationModuleData();
        $this->technicalReportModuleData();
        $this->incidentModuleData();
        app(LecturaController::class)->warmIndexCache();
        app(MedidorController::class)->warmIndexCache();
    }

    private function consumptionStats(): array
    {
        return OperationalCache::remember('consumo:stats:'.today()->toDateString(), function () {
            $summary = DB::query()
                ->selectRaw("
                    (SELECT COUNT(*) FROM lecturas WHERE fecha_lectura = ?) as lecturas_hoy,
                    (SELECT COUNT(*) FROM medidores WHERE estado = 'activo') as medidores_activos
                ", [today()->toDateString()])
                ->first();

            $lecturasHoy = (int) ($summary?->lecturas_hoy ?? 0);
            $medidoresActivos = (int) ($summary?->medidores_activos ?? 0);

            return [
                ['label' => 'Lecturas cargadas hoy', 'value' => $lecturasHoy, 'tone' => 'amber'],
                ['label' => 'Medidores activos', 'value' => $medidoresActivos, 'tone' => 'orange'],
                ['label' => 'Pendientes estimados', 'value' => max($medidoresActivos - $lecturasHoy, 0), 'tone' => 'slate'],
            ];
        });
    }

    private function recentReadings()
    {
        return OperationalCache::remember('consumo:recent-readings', function () {
            return DB::table('v_tecnico_lecturas_recientes')
                ->select([
                    'id_lectura',
                    'fecha_lectura',
                    'lectura_actual',
                    'consumo_m3',
                    'numero_serie',
                    'codigo_usuario',
                    'socio_nombre',
                ])
                ->orderByDesc('fecha_lectura')
                ->orderByDesc('id_lectura')
                ->limit(6)
                ->get();
        });
    }

    private function anomalyModuleData(): array
    {
        return OperationalCache::remember('anomalias:module-data', function () {
            $summary = MedidorAnomalia::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE prioridad = 'alta' AND estado <> 'resuelta') as altas,
                    COUNT(*) FILTER (WHERE estado IN ('pendiente', 'en_revision')) as pendientes,
                    COUNT(*) FILTER (WHERE estado = 'resuelta') as resueltas
                ")
                ->first();

            $recentAnomalias = MedidorAnomalia::query()
                ->select(['id_anomalia', 'fecha_reporte', 'tipo', 'prioridad', 'estado', 'descripcion', 'evidencia_path', 'monto_multa', 'id_medidor'])
                ->with([
                    'medidor:id_medidor,numero_serie,id_socio',
                    'medidor.socio:id_socio,numero_socio,id_persona',
                    'medidor.socio.persona:id_persona,nombres,apellidos',
                ])
                ->latest('fecha_reporte')
                ->limit(6)
                ->get();

            return [
                'moduleStats' => [
                    ['label' => 'Prioridad alta', 'value' => (int) ($summary?->altas ?? 0), 'tone' => 'rose'],
                    ['label' => 'Pendientes de revision', 'value' => (int) ($summary?->pendientes ?? 0), 'tone' => 'amber'],
                    ['label' => 'Resueltas', 'value' => (int) ($summary?->resueltas ?? 0), 'tone' => 'emerald'],
                ],
                'recentAnomalias' => $recentAnomalias,
            ];
        });
    }

    private function maintenanceMapData(): array
    {
        return OperationalCache::remember('mantenimiento:map-data', function () {
            $mapPoints = OrdenTecnica::query()
                ->select(['id_orden', 'tipo', 'estado', 'prioridad', 'zona', 'coord_x', 'coord_y', 'referencia'])
                ->where('tipo', 'mantenimiento')
                ->limit(18)
                ->get()
                ->map(fn (OrdenTecnica $order) => [
                    'label' => $order->zona ?: 'Zona sin nombre',
                    'status' => $order->estado,
                    'priority' => $order->prioridad,
                    'reference' => $order->referencia ?: 'Orden de campo',
                    'x' => $order->coord_x !== null ? (float) $order->coord_x : null,
                    'y' => $order->coord_y !== null ? (float) $order->coord_y : null,
                ])
                ->values();

            $zoneMap = $mapPoints
                ->groupBy(fn (array $point) => $point['label'])
                ->map(function ($items, $zone) {
                    $items = collect($items)->values();

                    return [
                        'zone' => $zone,
                        'critical' => $items->where('priority', 'alta')->count(),
                        'active' => $items->whereIn('status', ['pendiente', 'en_proceso'])->count(),
                        'items' => $items->map(function (array $item, int $index) {
                            $fallbackX = 16 + (($index % 4) * 22);
                            $fallbackY = 28 + (int) floor($index / 4) * 26;

                            return [
                                'label' => $item['reference'],
                                'status' => $item['status'],
                                'priority' => $item['priority'],
                                'x' => $item['x'] ?? $fallbackX,
                                'y' => $item['y'] ?? min($fallbackY, 84),
                            ];
                        })->all(),
                    ];
                })
                ->values();

            return compact('mapPoints', 'zoneMap');
        });
    }

    private function operationModuleData(): array
    {
        return OperationalCache::remember('operacion:module-data', function () {
            $summary = OperacionSistema::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE estado = 'operativa') as operativas,
                    COUNT(*) FILTER (WHERE estado = 'ajustada') as ajustadas,
                    COUNT(*) FILTER (WHERE estado IN ('alerta', 'mantenimiento')) as alertas
                ")
                ->first();

            $recentOperations = OperacionSistema::query()
                ->select(['id_operacion', 'fecha_operacion', 'tipo_operacion', 'zona', 'estado', 'horario', 'descripcion'])
                ->latest('fecha_operacion')
                ->latest('id_operacion')
                ->limit(8)
                ->get();

            return [
                'moduleStats' => [
                    ['label' => 'Operativas', 'value' => (int) ($summary?->operativas ?? 0), 'tone' => 'emerald'],
                    ['label' => 'Ajustadas', 'value' => (int) ($summary?->ajustadas ?? 0), 'tone' => 'orange'],
                    ['label' => 'Alertas', 'value' => (int) ($summary?->alertas ?? 0), 'tone' => 'rose'],
                ],
                'recentOperations' => $recentOperations,
            ];
        });
    }

    private function technicalReportModuleData(): array
    {
        return OperationalCache::remember('reportes-tecnicos:module-data', function () {
            $summary = ReporteTecnico::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE estado = 'emitido') as emitidos,
                    COUNT(*) FILTER (WHERE estado = 'borrador') as borradores,
                    COUNT(*) FILTER (WHERE estado = 'cerrado') as cerrados
                ")
                ->first();

            $recentReports = ReporteTecnico::query()
                ->select(['id_reporte', 'titulo', 'categoria', 'fecha_reporte', 'estado', 'resumen'])
                ->latest('fecha_reporte')
                ->latest('id_reporte')
                ->limit(8)
                ->get();

            return [
                'moduleStats' => [
                    ['label' => 'Emitidos', 'value' => (int) ($summary?->emitidos ?? 0), 'tone' => 'emerald'],
                    ['label' => 'Borradores', 'value' => (int) ($summary?->borradores ?? 0), 'tone' => 'amber'],
                    ['label' => 'Cerrados', 'value' => (int) ($summary?->cerrados ?? 0), 'tone' => 'slate'],
                ],
                'recentReports' => $recentReports,
            ];
        });
    }

    private function incidentModuleData(): array
    {
        return OperationalCache::remember('incidencias:module-data', function () {
            $summary = IncidenciaTecnica::query()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE prioridad = 'alta' AND estado <> 'cerrada') as altas,
                    COUNT(*) FILTER (WHERE estado IN ('abierta', 'en_proceso')) as abiertas,
                    COUNT(*) FILTER (WHERE estado = 'cerrada') as cerradas
                ")
                ->first();

            $recentIncidencias = IncidenciaTecnica::query()
                ->select(['id_incidencia', 'tipo', 'prioridad', 'estado', 'zona', 'fecha_reporte', 'descripcion', 'evidencia_path'])
                ->latest('fecha_reporte')
                ->limit(8)
                ->get();

            return [
                'moduleStats' => [
                    ['label' => 'Prioridad alta', 'value' => (int) ($summary?->altas ?? 0), 'tone' => 'rose'],
                    ['label' => 'Abiertas', 'value' => (int) ($summary?->abiertas ?? 0), 'tone' => 'amber'],
                    ['label' => 'Cerradas', 'value' => (int) ($summary?->cerradas ?? 0), 'tone' => 'emerald'],
                ],
                'recentIncidencias' => $recentIncidencias,
            ];
        });
    }

    private function orderModuleView(string $type, string $view, string $title, string $description): View
    {
        [$stats, $orders] = $this->orderSummary($type);
        $selectedSocioId = request()->integer('socio') ?: (int) request()->old('id_socio');

        return view($view, [
            'moduleTitle' => $title,
            'moduleDescription' => $description,
            'moduleStats' => $stats,
            'recentOrders' => $orders,
            'socios' => $this->socioCatalog(60, $selectedSocioId ?: null),
            'zonas' => $this->zoneCatalog(),
            'billingSignals' => $this->billingSignals(),
            'selectedSocioId' => $selectedSocioId,
            'attentionQueue' => $type === 'corte'
                ? $this->corteCandidates()
                : ($type === 'reconexion' ? $this->reconnectionCandidates() : collect()),
        ]);
    }

    private function orderSummary(string $type): array
    {
        return OperationalCache::remember("orders:{$type}:summary", function () use ($type) {
            $summary = OrdenTecnica::query()
                ->where('tipo', $type)
                ->selectRaw("
                    COUNT(*) FILTER (WHERE estado = 'pendiente') as pendientes,
                    COUNT(*) FILTER (WHERE estado = 'en_proceso') as en_proceso,
                    COUNT(*) FILTER (WHERE estado = 'completada') as completadas
                ")
                ->first();

            $orders = DB::table('v_tecnico_ordenes_recientes')
                ->where('tipo', $type)
                ->orderByDesc('fecha_programada')
                ->orderByDesc('id_orden')
                ->limit(8)
                ->get()
                ->map(fn ($order) => $this->orderRowForView($order));

            return [[
                ['label' => 'Pendientes', 'value' => (int) ($summary?->pendientes ?? 0), 'tone' => 'amber'],
                ['label' => 'En proceso', 'value' => (int) ($summary?->en_proceso ?? 0), 'tone' => 'orange'],
                ['label' => 'Completadas', 'value' => (int) ($summary?->completadas ?? 0), 'tone' => 'emerald'],
            ], $orders];
        });
    }

    private function orderRowForView(object $order): object
    {
        $socio = $order->id_socio ? (object) [
            'id_socio' => $order->id_socio,
            'codigo_display' => $order->codigo_display ?: 'Sin socio',
            'persona' => (object) [
                'nombre_completo' => $order->socio_nombre ?: 'Sin socio',
            ],
            'sector' => (object) [
                'nombre' => $order->sector_nombre ?: ($order->zona ?: 'Sin zona'),
            ],
        ] : null;

        $order->socio = $socio;
        $order->medidor = $order->id_medidor ? (object) [
            'id_medidor' => $order->id_medidor,
            'numero_serie' => $order->medidor_serie,
        ] : null;

        return $order;
    }

    private function storeOrder(Request $request, string $type, string $redirectRoute, string $successMessage): RedirectResponse
    {
        $data = $request->validate([
            'id_socio' => ['nullable', 'exists:socios,id_socio'],
            'id_medidor' => ['nullable', 'exists:medidores,id_medidor'],
            'fecha_programada' => ['required', 'date'],
            'fecha_ejecucion' => ['nullable', 'date'],
            'estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completada', 'cancelada'])],
            'prioridad' => ['required', Rule::in(['alta', 'media', 'baja'])],
            'zona' => ['nullable', 'string', 'max:120'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['required', 'string', 'max:1200'],
            'coord_x' => ['nullable', 'numeric', 'between:-90,90'],
            'coord_y' => ['nullable', 'numeric', 'between:-180,180'],
            'numero_serie' => ['nullable', 'string', 'max:60'],
            'lectura_inicial' => ['nullable', 'numeric', 'min:0'],
            'evidencia' => ['nullable', 'image', 'max:4096'],
        ]);
        $numeroSerie = trim((string) ($data['numero_serie'] ?? ''));
        $lecturaInicial = $data['lectura_inicial'] ?? null;
        unset($data['numero_serie'], $data['lectura_inicial'], $data['evidencia']);

        $signal = ! empty($data['id_socio'])
            ? ($this->billingSignals()->get((int) $data['id_socio']) ?? null)
            : null;

        $socio = ! empty($data['id_socio'])
            ? Socio::query()->with('sector:id_sector,nombre')->find($data['id_socio'])
            : null;

        if (blank($data['zona']) && $socio?->sector?->nombre) {
            $data['zona'] = $socio->sector->nombre;
        }

        if (blank($data['referencia'])) {
            $data['referencia'] = $this->buildOrderReference($type, $signal);
        }

        $currentUser = Auth::user();
        $isAdmin = $currentUser instanceof User && $currentUser->hasRole('administrador');

        if ($type === 'reconexion' && ! $isAdmin) {
            $data['estado'] = 'pendiente';
        }

        $duplicate = $this->findDuplicateOrder($type, $data);

        if ($duplicate && ! in_array($duplicate->estado, ['pendiente', 'en_proceso'], true)) {
            return redirect()->route($redirectRoute)->with('success', 'La orden ya estaba registrada; no se creo un duplicado.');
        }

        $orderPayload = $data + [
            'coord_x' => $this->normalizeCoordinate($data['coord_x'] ?? null),
            'coord_y' => $this->normalizeCoordinate($data['coord_y'] ?? null),
            'tipo' => $type,
            'id_empleado' => $this->resolveEmpleadoId(),
        ];

        if ($request->hasFile('evidencia')) {
            $evidencePath = $this->storeEvidence($request, 'evidencia', 'tecnico/ordenes');
            if ($evidencePath) {
                $orderPayload['descripcion'] = trim(($orderPayload['descripcion'] ?? '')."\nEvidencia: ".$evidencePath);
            }
        }

        if ($duplicate) {
            $duplicate->fill($orderPayload)->save();
            $order = $duplicate;
        } else {
            $order = OrdenTecnica::create($orderPayload);
        }

        if ($order && $type === 'instalacion' && $socio && $order->estado === 'completada') {
            $this->completeInstallation($order, $socio, $numeroSerie, $lecturaInicial, $data['fecha_ejecucion'] ?? $data['fecha_programada']);
        }

        if ($order && $type === 'corte' && $socio && $order->estado === 'completada') {
            $socio->update(['estado' => 'cortado']);
        }

        if ($order && $type === 'reconexion' && $socio && $order->estado === 'completada') {
            $socio->update(['estado' => 'activo']);
        }

        $this->invalidateOperationalCaches();

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }

    private function findDuplicateOrder(string $type, array $data): ?OrdenTecnica
    {
        if (empty($data['id_socio']) && blank($data['referencia'] ?? null)) {
            return null;
        }

        return OrdenTecnica::query()
            ->where('tipo', $type)
            ->when(! empty($data['id_socio']), fn ($query) => $query->where('id_socio', $data['id_socio']))
            ->when(empty($data['id_socio']) && filled($data['referencia'] ?? null), fn ($query) => $query->where('referencia', $data['referencia']))
            ->whereDate('fecha_programada', $data['fecha_programada'])
            ->whereIn('estado', ['pendiente', 'en_proceso', 'completada'])
            ->orderByRaw("CASE WHEN estado IN ('pendiente', 'en_proceso') THEN 0 ELSE 1 END")
            ->orderByDesc('id_orden')
            ->first();
    }

    private function completeInstallation(OrdenTecnica $order, Socio $socio, string $numeroSerie, mixed $lecturaInicial, string $installationDate): void
    {
        $medidor = $order->medidor ?: $socio->medidores()->orderByDesc('id_medidor')->first();

        if (! $medidor) {
            return;
        }

        if ($numeroSerie !== '' && Medidor::query()
            ->where('numero_serie', $numeroSerie)
            ->where('id_medidor', '<>', $medidor->id_medidor)
            ->exists()) {
            throw ValidationException::withMessages([
                'numero_serie' => 'El numero de medidor ya esta registrado en otro socio.',
            ]);
        }

        $medidor->update([
            'numero_serie' => $numeroSerie !== '' ? $numeroSerie : $medidor->numero_serie,
            'fecha_instalacion' => $installationDate,
            'latitud' => $order->coord_x ?? $medidor->latitud,
            'longitud' => $order->coord_y ?? $medidor->longitud,
            'estado' => 'activo',
            'id_empleado_instalador' => $this->resolveEmpleadoId(),
        ]);

        if ($lecturaInicial !== null && ! DB::table('lecturas')
            ->where('id_medidor', $medidor->id_medidor)
            ->whereDate('fecha_lectura', $installationDate)
            ->exists()) {
            Lectura::create([
                'fecha_lectura' => $installationDate,
                'lectura_anterior' => (float) $lecturaInicial,
                'lectura_actual' => (float) $lecturaInicial,
                'observaciones' => 'Lectura inicial registrada al completar instalacion.',
                'latitud' => $order->coord_x,
                'longitud' => $order->coord_y,
                'id_medidor' => $medidor->id_medidor,
                'id_empleado' => $this->resolveEmpleadoId(),
            ]);
        }

        $socioUpdates = [];
        if ($order->coord_x !== null && $socio->latitud === null) {
            $socioUpdates['latitud'] = $order->coord_x;
        }
        if ($order->coord_y !== null && $socio->longitud === null) {
            $socioUpdates['longitud'] = $order->coord_y;
        }
        if ($socio->estado !== 'activo') {
            $socioUpdates['estado'] = 'suspendido';
        }
        if ($socioUpdates !== []) {
            $socio->update($socioUpdates);
        }

        Notificacion::create([
            'tipo' => 'instalacion_completada',
            'mensaje' => 'Instalacion completada para '.$socio->codigo_display.'. El administrador debe validar y activar el socio antes de facturar.',
            'fecha_envio' => now(),
            'enviado' => false,
            'canal' => 'sistema',
            'id_socio' => $socio->id_socio,
        ]);
    }

    private function consumptionCatalog()
    {
        return OperationalCache::remember('consumo:catalogo:legacy', function () {
            return $this->meterCatalogQuery()
                ->get()
                ->map(function ($medidor) {
                    $socio = $medidor->socio;

                    return (object) [
                        'id_medidor' => $medidor->id_medidor,
                        'numero_serie' => $medidor->numero_serie,
                        'socio_nombre' => $socio?->persona?->nombre_completo ?? 'Sin socio',
                        'codigo_usuario' => $socio?->codigo_display ?? '-',
                        'zona' => $socio?->sector?->nombre ?? 'Sin zona',
                        'direccion' => $socio?->direccion ?: 'Sin direccion',
                        'latitud' => $medidor->latitud ?? $socio?->latitud,
                        'longitud' => $medidor->longitud ?? $socio?->longitud,
                        'lectura_sugerida' => (float) ($medidor->ultima_lectura_actual ?? 0),
                        'ultima_fecha' => $medidor->ultima_fecha_lectura
                            ? Carbon::parse($medidor->ultima_fecha_lectura)->format('d/m/Y')
                            : null,
                    ];
                });
        });
    }

    private function socioCatalog(int $limit = 60, ?int $selectedId = null)
    {
        $rows = OperationalCache::remember("socios:catalogo:v2:limit:{$limit}", fn () => $this->socioCatalogRows('', $limit));

        if ($selectedId && ! $rows->contains(fn ($socio) => (int) $socio->id_socio === $selectedId)) {
            $selected = $this->socioCatalogRows('', 1, $selectedId)->first();

            if ($selected) {
                $rows = $rows->prepend($selected);
            }
        }

        return $rows;
    }

    private function socioCatalogRows(string $search = '', int $limit = 60, ?int $idSocio = null)
    {
        return DB::table('v_tecnico_socios_catalogo')
            ->when($idSocio, fn ($query) => $query->where('id_socio', $idSocio))
            ->when($search !== '', function ($query) use ($search) {
                $term = "%{$search}%";

                $query->where(function ($builder) use ($term) {
                    $builder->where('codigo_display', 'ilike', $term)
                        ->orWhere('socio_nombre', 'ilike', $term)
                        ->orWhere('sector_nombre', 'ilike', $term)
                        ->orWhere('cedula_identidad', 'ilike', $term);
                });
            })
            ->orderBy('codigo_display')
            ->limit($limit)
            ->get()
            ->map(function ($socio) {
                $socio->persona = (object) [
                    'nombre_completo' => $socio->socio_nombre ?: 'Sin socio',
                ];
                $socio->sector = (object) [
                    'nombre' => $socio->sector_nombre ?: 'Sin zona',
                ];

                return $socio;
            });
    }

    private function socioCatalogRowsForIds(array $ids)
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('v_tecnico_socios_catalogo')
            ->whereIn('id_socio', $ids)
            ->get()
            ->map(function ($socio) {
                $socio->persona = (object) [
                    'nombre_completo' => $socio->socio_nombre ?: 'Sin socio',
                ];
                $socio->sector = (object) [
                    'nombre' => $socio->sector_nombre ?: 'Sin zona',
                ];

                return $socio;
            });
    }

    private function zoneCatalog()
    {
        return OperationalCache::remember('zonas:catalogo', function () {
            return DB::table('sectores')
                ->select(['id_sector', 'nombre', 'zona'])
                ->orderBy('nombre')
                ->get();
        });
    }

    private function optimizedConsumptionCatalog(int $limit = 60)
    {
        return $this->catalogResults('consumo', 'medidores', '', $limit, function () use ($limit) {
            return $this->meterCatalogRows('', $limit);
        });
    }

    private function meterCatalogRows(string $search = '', int $limit = 60)
    {
        return $this->catalogResults('consumo', 'medidores-gps', $search, $limit, function () use ($search, $limit) {
            return DB::table('v_tecnico_medidores_consumo')
            ->select([
                'id_medidor',
                'numero_serie',
                'id_socio',
                'codigo_usuario',
                'socio_nombre',
                'zona',
                'direccion',
                'medidor_latitud',
                'medidor_longitud',
                'socio_latitud',
                'socio_longitud',
                'lectura_sugerida',
                'ultima_fecha',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $term = "%{$search}%";

                $query->where(function ($builder) use ($term) {
                    $builder->where('numero_serie', 'ilike', $term)
                        ->orWhere('codigo_usuario', 'ilike', $term)
                        ->orWhere('socio_nombre', 'ilike', $term)
                        ->orWhere('zona', 'ilike', $term);
                });
            })
                ->orderBy('numero_serie')
                ->limit($limit)
                ->get()
                ->map(function ($medidor) {
                    return (object) [
                        'id_medidor' => $medidor->id_medidor,
                        'numero_serie' => $medidor->numero_serie,
                        'socio_nombre' => $medidor->socio_nombre ?: 'Sin socio',
                        'codigo_usuario' => $medidor->codigo_usuario ?: '-',
                        'zona' => $medidor->zona ?: 'Sin zona',
                        'direccion' => $medidor->direccion ?: 'Sin direccion',
                        'latitud' => $medidor->medidor_latitud ?? $medidor->socio_latitud,
                        'longitud' => $medidor->medidor_longitud ?? $medidor->socio_longitud,
                        'lectura_sugerida' => (float) ($medidor->lectura_sugerida ?? 0),
                        'ultima_fecha' => $medidor->ultima_fecha
                            ? Carbon::parse($medidor->ultima_fecha)->format('d/m/Y')
                            : null,
                    ];
                });
        });
    }

    private function catalogResults(string $scope, string $entity, string $search, int $limit, callable $callback): mixed
    {
        $cacheKey = $this->catalogCacheKey($scope, $entity, $search, $limit);

        return Cache::remember($cacheKey, now()->addMinutes(10), $callback);
    }

    private function catalogCacheKey(string $scope, string $entity, string|int $search = '', ?int $limit = null): string
    {
        if ($limit === null) {
            $limit = (int) $search;
            $search = $entity;
            $entity = $scope;
            $prefix = 'tecnico:catalog:'.$entity;
        } else {
            $prefix = "tecnico:catalog:{$scope}:{$entity}";
        }

        $normalizedSearch = mb_strtolower(trim((string) $search));
        $versionKey = "{$prefix}:version";
        Cache::add($versionKey, 1, now()->addYears(2));
        $version = Cache::get($versionKey, 1);

        return "{$prefix}:v{$version}:".md5($normalizedSearch.'|'.$limit);
    }

    private function meterCatalogQuery(): Builder
    {
        $ultimaLecturaActual = Lectura::query()
            ->select('lectura_actual')
            ->whereColumn('id_medidor', 'medidores.id_medidor')
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura')
            ->limit(1);

        $ultimaFechaLectura = Lectura::query()
            ->select('fecha_lectura')
            ->whereColumn('id_medidor', 'medidores.id_medidor')
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura')
            ->limit(1);

        return Medidor::query()
            ->select(['id_medidor', 'numero_serie', 'id_socio', 'latitud', 'longitud'])
            ->selectSub($ultimaLecturaActual, 'ultima_lectura_actual')
            ->selectSub($ultimaFechaLectura, 'ultima_fecha_lectura')
            ->with([
                'socio:id_socio,numero_socio,id_persona,id_sector,direccion,latitud,longitud',
                'socio.persona:id_persona,nombres,apellidos',
                'socio.sector:id_sector,nombre',
            ])
            ->where('estado', 'activo')
            ->orderBy('numero_serie');
    }

    private function storeEvidence(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        if (! $file || ! $file->isValid()) {
            return null;
        }

        return PrivateMedia::storeImage($file, $directory, 'evidencia');
    }

    private function billingSignals()
    {
        return OperationalCache::remember('billing:signals', function () {
            $pagosPorFactura = DB::table('cobros')
                ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
                ->groupBy('id_factura');

            $deudaPorSocio = DB::table('facturas as f')
                ->leftJoinSub($pagosPorFactura, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
                ->selectRaw("
                    f.id_socio,
                    ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as total_pendiente,
                    COUNT(*) FILTER (WHERE f.estado IN ('vencida', 'parcial', 'pendiente') AND GREATEST(f.total - COALESCE(cp.pagado, 0), 0) > 0) as facturas_abiertas,
                    COUNT(*) FILTER (WHERE f.estado = 'vencida' AND GREATEST(f.total - COALESCE(cp.pagado, 0), 0) > 0) as facturas_vencidas
                ")
                ->groupBy('f.id_socio');

            $ultimoCobroPorSocio = DB::table('cobros as c')
                ->join('facturas as f', 'f.id_factura', '=', 'c.id_factura')
                ->where('c.estado', '<>', 'anulado')
                ->selectRaw('
                    f.id_socio,
                    MAX(c.fecha_cobro) as ultima_fecha_pago,
                    MAX(c.id_cobro) as ultimo_cobro_id
                ')
                ->groupBy('f.id_socio');

            $limiteAnomalia = now()->subDays(30)->toDateString();
            $anomaliasPorSocio = DB::table('medidor_anomalias as ma')
                ->join('medidores as m', 'm.id_medidor', '=', 'ma.id_medidor')
                ->leftJoin('facturas as fm', 'fm.id_factura', '=', 'ma.id_factura_multa')
                ->selectRaw("
                    m.id_socio,
                    COUNT(*) FILTER (WHERE ma.estado IN ('pendiente', 'en_revision')) as anomalias_abiertas,
                    COUNT(*) FILTER (WHERE ma.estado IN ('pendiente', 'en_revision') AND ma.fecha_reporte <= ?) as anomalias_vencidas,
                    COUNT(*) FILTER (
                        WHERE ma.estado IN ('pendiente', 'en_revision')
                        AND ma.id_factura_multa IS NOT NULL
                        AND COALESCE(fm.estado, 'pendiente') IN ('pendiente', 'parcial', 'vencida')
                    ) as multas_activas,
                    ROUND(SUM(
                        CASE
                            WHEN ma.estado IN ('pendiente', 'en_revision')
                             AND ma.id_factura_multa IS NOT NULL
                             AND COALESCE(fm.estado, 'pendiente') IN ('pendiente', 'parcial', 'vencida')
                            THEN COALESCE(ma.monto_multa, 0)
                            ELSE 0
                        END
                    ), 2) as multas_pendientes
                ", [$limiteAnomalia])
                ->groupBy('m.id_socio');

            return Socio::query()
                ->select(['socios.id_socio'])
                ->leftJoinSub($deudaPorSocio, 'deuda', fn ($join) => $join->on('deuda.id_socio', '=', 'socios.id_socio'))
                ->leftJoinSub($ultimoCobroPorSocio, 'pagos', fn ($join) => $join->on('pagos.id_socio', '=', 'socios.id_socio'))
                ->leftJoinSub($anomaliasPorSocio, 'anomalias', fn ($join) => $join->on('anomalias.id_socio', '=', 'socios.id_socio'))
                ->get([
                    'socios.id_socio',
                    DB::raw('COALESCE(deuda.total_pendiente, 0) as total_pendiente'),
                    DB::raw('COALESCE(deuda.facturas_abiertas, 0) as facturas_abiertas'),
                    DB::raw('COALESCE(deuda.facturas_vencidas, 0) as facturas_vencidas'),
                    DB::raw('pagos.ultima_fecha_pago as ultima_fecha_pago'),
                    DB::raw('pagos.ultimo_cobro_id as ultimo_cobro_id'),
                    DB::raw('COALESCE(anomalias.anomalias_abiertas, 0) as anomalias_abiertas'),
                    DB::raw('COALESCE(anomalias.anomalias_vencidas, 0) as anomalias_vencidas'),
                    DB::raw('COALESCE(anomalias.multas_activas, 0) as multas_activas'),
                    DB::raw('COALESCE(anomalias.multas_pendientes, 0) as multas_pendientes'),
                ])
                ->mapWithKeys(function ($row) {
                    $facturasAbiertas = (int) $row->facturas_abiertas;
                    $facturasVencidas = (int) $row->facturas_vencidas;
                    $anomaliasAbiertas = (int) $row->anomalias_abiertas;
                    $anomaliasVencidas = (int) $row->anomalias_vencidas;
                    $multasActivas = (int) $row->multas_activas;
                    $totalPendiente = round((float) $row->total_pendiente, 2);

                    return [(int) $row->id_socio => [
                        'total_pendiente' => $totalPendiente,
                        'facturas_abiertas' => $facturasAbiertas,
                        'facturas_vencidas' => $facturasVencidas,
                        'ultima_fecha_pago' => $row->ultima_fecha_pago,
                        'ultimo_cobro_id' => $row->ultimo_cobro_id ? (int) $row->ultimo_cobro_id : null,
                        'anomalias_abiertas' => $anomaliasAbiertas,
                        'anomalias_vencidas' => $anomaliasVencidas,
                        'anomalias_reincidentes' => $anomaliasAbiertas >= 2 ? $anomaliasAbiertas : 0,
                        'multas_activas' => $multasActivas,
                        'multas_pendientes' => round((float) $row->multas_pendientes, 2),
                        'recomienda_corte' => $facturasAbiertas >= 3
                            || $facturasVencidas >= 3
                            || $anomaliasVencidas > 0
                            || $anomaliasAbiertas >= 2
                            || ($multasActivas > 0 && $totalPendiente > 0),
                        'recomienda_reconexion' => $totalPendiente <= 0 && ! empty($row->ultima_fecha_pago),
                    ]];
                });
        });
    }

    private function corteCandidates()
    {
        $signals = $this->billingSignals();
        $openCutSocios = OperationalCache::remember('corte:open-socios', fn () => OrdenTecnica::query()
            ->where('tipo', 'corte')
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->whereNotNull('id_socio')
            ->pluck('id_socio')
            ->map(fn ($id) => (int) $id)
            ->all());

        $candidateIds = $signals
            ->filter(fn (array $signal) => ! empty($signal['recomienda_corte']))
            ->reject(fn (array $signal, $idSocio) => in_array((int) $idSocio, $openCutSocios, true))
            ->sortByDesc('total_pendiente')
            ->keys()
            ->take(6)
            ->map(fn ($id) => (int) $id)
            ->values();

        $socios = $this->socioCatalogRowsForIds($candidateIds->all())->keyBy('id_socio');

        return $candidateIds
            ->map(function (int $idSocio) use ($signals, $socios) {
                $socio = $socios->get($idSocio);
                $signal = $signals->get($idSocio);

                if (! $socio || ! $signal) {
                    return null;
                }

                $motivos = [];
                if (($signal['facturas_abiertas'] ?? 0) >= 3 || ($signal['facturas_vencidas'] ?? 0) >= 3) {
                    $motivos[] = 'Debe tres o mas facturas de agua';
                }
                if (($signal['anomalias_vencidas'] ?? 0) > 0) {
                    $motivos[] = 'Tiene anomalias sin regularizar por demasiado tiempo';
                }
                if (($signal['anomalias_reincidentes'] ?? 0) >= 2) {
                    $motivos[] = 'Existe reincidencia de anomalias tecnicas';
                }

                return [
                    'id_socio' => $socio->id_socio,
                    'codigo' => $socio->codigo_display,
                    'nombre' => $socio->persona?->nombre_completo ?? 'Sin socio',
                    'zona' => $socio->sector?->nombre ?? 'Sin zona',
                    'total_pendiente' => $signal['total_pendiente'],
                    'motivo' => implode(' · ', $motivos) ?: 'Revision tecnica recomendada',
                ];
            })
            ->filter()
            ->values();
    }

    private function reconnectionCandidates()
    {
        $signals = $this->billingSignals();
        $openReconnections = OperationalCache::remember('reconexion:open-socios', fn () => OrdenTecnica::query()
            ->where('tipo', 'reconexion')
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->whereNotNull('id_socio')
            ->pluck('id_socio')
            ->map(fn ($id) => (int) $id)
            ->all());
        $latestCuts = OperationalCache::remember('reconexion:latest-cuts', fn () => OrdenTecnica::query()
            ->select(['id_orden', 'id_socio', 'fecha_programada', 'estado'])
            ->where('tipo', 'corte')
            ->whereNotNull('id_socio')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id_orden')
            ->get()
            ->unique('id_socio')
            ->keyBy('id_socio'));

        $candidateIds = $signals
            ->filter(fn (array $signal, $idSocio) => ! empty($signal['recomienda_reconexion']) && $latestCuts->has((int) $idSocio))
            ->reject(fn (array $signal, $idSocio) => in_array((int) $idSocio, $openReconnections, true))
            ->keys()
            ->take(6)
            ->map(fn ($id) => (int) $id)
            ->values();

        $socios = $this->socioCatalogRowsForIds($candidateIds->all())->keyBy('id_socio');

        return $candidateIds
            ->map(function (int $idSocio) use ($signals, $latestCuts, $socios) {
                $socio = $socios->get($idSocio);
                $signal = $signals->get($idSocio);
                $latestCut = $latestCuts->get($idSocio);

                if (! $socio || ! $signal || ! $latestCut) {
                    return null;
                }

                return [
                    'id_socio' => $socio->id_socio,
                    'codigo' => $socio->codigo_display,
                    'nombre' => $socio->persona?->nombre_completo ?? 'Sin socio',
                    'zona' => $socio->sector?->nombre ?? 'Sin zona',
                    'ultima_fecha_pago' => $signal['ultima_fecha_pago'],
                    'ultimo_cobro_id' => $signal['ultimo_cobro_id'],
                    'motivo' => 'Ya canceló facturas y/o multas pendientes; requiere aprobación de secretaria o administración.',
                ];
            })
            ->filter()
            ->values();
    }

    private function attachAnomalyPenalty(MedidorAnomalia $anomalia): ?Factura
    {
        if ($anomalia->id_factura_multa) {
            return Factura::query()->find($anomalia->id_factura_multa);
        }

        $monto = round((float) ($this->generalSettings()['multa_retraso'] ?? 0), 2);
        $socioId = $anomalia->medidor?->id_socio;

        if ($monto <= 0 || ! $socioId) {
            return null;
        }

        $factura = Factura::query()
            ->where('id_socio', $socioId)
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('fecha_emision')
            ->orderBy('id_factura')
            ->first();

        if (! $factura) {
            return null;
        }

        $factura->update([
            'recargo_mora' => round((float) $factura->recargo_mora + $monto, 2),
        ]);

        $anomalia->update([
            'monto_multa' => $monto,
            'id_factura_multa' => $factura->id_factura,
        ]);

        return $factura->fresh();
    }

    private function createIncidentExpense(Request $request, IncidenciaTecnica $incidencia): void
    {
        $monto = round((float) $request->input('gasto_monto', 0), 2);
        $materiales = trim((string) $request->input('materiales_utilizados', ''));
        $concepto = trim((string) $request->input('gasto_concepto', ''));

        if ($monto <= 0 && $materiales === '' && $concepto === '') {
            return;
        }

        Gasto::create([
            'fecha_gasto' => now()->toDateString(),
            'concepto' => $concepto !== '' ? $concepto : 'Atencion de incidencia tecnica',
            'categoria' => trim((string) $request->input('gasto_categoria', 'Incidencias')) ?: 'Incidencias',
            'descripcion' => trim(
                'Incidencia #'.$incidencia->id_incidencia
                .' en '.$incidencia->zona
                .'. '.$incidencia->descripcion
                .($materiales !== '' ? ' Materiales: '.$materiales : '')
            ),
            'monto' => max($monto, 0),
            'id_empleado' => $this->resolveEmpleadoId(),
        ]);

        Cache::add('gastos:index:version', 1, now()->addYears(2));
        Cache::increment('gastos:index:version');
    }

    private function normalizeCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 7);
    }

    private function generalSettings(): array
    {
        return SystemSetting::getValue('general', [
            'multa_retraso' => 0,
        ]);
    }

    private function invalidateOperationalCaches(): void
    {
        foreach ([
            'tecnico:consumo:catalogo',
            'tecnico:consumo:catalogo:v2',
            'tecnico:consumo:catalogo:v3',
            'tecnico:consumo:stats',
            'tecnico:consumo:recent-readings',
            'tecnico:billing-signals',
            'tecnico:corte:open-socios',
            'tecnico:reconexion:open-socios',
            'tecnico:reconexion:latest-cuts',
            'tecnico:corte:candidates',
            'tecnico:reconexion:candidates',
            'dashboard:tecnico:upcoming-readings',
            'dashboard:tecnico:reading-calendar:'.now()->format('Y-m'),
            'dashboard:tecnico:medidores-total',
            'dashboard:pending-reconnections',
            'dashboard:completed-installations',
            'dashboard:recent-operational-expenses',
            'api.dashboard.tecnico',
        ] as $key) {
            Cache::forget($key);
        }

        foreach ([
            'consumo:catalogo:legacy',
            'consumo:stats:'.today()->toDateString(),
            'consumo:recent-readings',
            'anomalias:module-data',
            'mantenimiento:map-data',
            'operacion:module-data',
            'reportes-tecnicos:module-data',
            'incidencias:module-data',
            'orders:corte:summary',
            'orders:reconexion:summary',
            'orders:instalacion:summary',
            'orders:mantenimiento:summary',
            'socios:catalogo:limit:50',
            'socios:catalogo:limit:60',
            'socios:catalogo:v2:limit:50',
            'socios:catalogo:v2:limit:60',
            'zonas:catalogo',
            'billing:signals',
            'mapa-operativo:markers',
            'corte:open-socios',
            'reconexion:open-socios',
            'reconexion:latest-cuts',
            'dashboard:tecnico:upcoming-readings',
            'dashboard:tecnico:reading-calendar:'.now()->format('Y-m'),
            'api-dashboard-tecnico',
        ] as $name) {
            OperationalCache::forget($name);
        }

        Cache::add('tecnico:catalog:consumo:medidores:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores:version');
        Cache::add('tecnico:catalog:consumo:medidores-gps:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores-gps:version');
    }

    private function buildOrderReference(string $type, ?array $signal): ?string
    {
        if (! $signal) {
            return null;
        }

        if ($type === 'corte' && $signal['total_pendiente'] > 0) {
            return 'Deuda pendiente Bs '.number_format($signal['total_pendiente'], 2)
                .' · '.$signal['facturas_abiertas'].' factura(s) abiertas';
        }

        if ($type === 'reconexion' && ! empty($signal['ultima_fecha_pago'])) {
            $fecha = Carbon::parse($signal['ultima_fecha_pago'])->format('d/m/Y');
            $cobro = $signal['ultimo_cobro_id'] ? ' · Cobro #'.$signal['ultimo_cobro_id'] : '';

            return 'Pago validado el '.$fecha.$cobro;
        }

        return null;
    }

    private function resolveEmpleadoId(): int
    {
        return Auth::user()?->persona?->empleado?->id_empleado
            ?? Empleado::query()->where('estado', 'activo')->orderBy('id_empleado')->value('id_empleado');
    }
}
