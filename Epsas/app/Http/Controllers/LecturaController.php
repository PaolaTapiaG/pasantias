<?php

namespace App\Http\Controllers;

use App\Events\LecturaRegistrada;
use App\Models\Empleado;
use App\Models\Lectura;
use App\Support\PrivateMedia;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class LecturaController extends Controller
{
    public function index(Request $request): View
    {
        return view('lecturas.index', [
            'lecturas' => $this->readingPaginator($request),
            'stats' => $this->readingStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/lecturas', 'GET');

        $this->readingPaginator($request, url('/admin/lecturas'));
        $this->readingStats();
        $this->medidoresDisponibles();
    }

    private function readingPaginator(Request $request, ?string $path = null)
    {
        $query = DB::table('v_tecnico_lecturas_index')
            ->select([
                'id_lectura',
                'fecha_lectura',
                'lectura_anterior',
                'lectura_actual',
                'consumo_m3',
                'observaciones',
                'id_medidor',
                'id_empleado',
                'numero_serie',
                'id_socio',
                'codigo_display',
                'socio_nombre',
                'cedula_identidad',
                'lector_nombre',
            ])
            ->addSelect([
                'evidencia_path' => DB::table('lecturas as evidence_reading')
                    ->select('evidence_reading.evidencia_path')
                    ->whereColumn('evidence_reading.id_lectura', 'v_tecnico_lecturas_index.id_lectura')
                    ->limit(1),
            ])
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura');

        if ($request->filled('buscar') && strlen(trim((string) $request->buscar)) >= 2) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('numero_serie', 'ilike', "%{$term}%")
                    ->orWhere('codigo_display', 'ilike', "%{$term}%")
                    ->orWhere('socio_nombre', 'ilike', "%{$term}%")
                    ->orWhere('cedula_identidad', 'ilike', "%{$term}%")
                    ->orWhereIn('id_medidor', DB::table('medidores as sm')
                        ->join('socios as ss', 'ss.id_socio', '=', 'sm.id_socio')
                        ->join('sectores as sec', 'sec.id_sector', '=', 'ss.id_sector')
                        ->where(function ($zoneQuery) use ($term) {
                            $zoneQuery->where('sec.nombre', 'ilike', "%{$term}%")
                                ->orWhere('sec.zona', 'ilike', "%{$term}%");
                        })
                        ->select('sm.id_medidor'));
            });
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_lectura', [$request->desde, $request->hasta]);
        }

        $cacheKey = 'lecturas.index.evidence.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('billing', $cacheKey, fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/lecturas'))
            ->appends($request->query())
            ->through(fn ($row) => $this->readingRowForView($row)));
    }

    private function readingRowForView(object $row): object
    {
        $row->fecha_lectura = $row->fecha_lectura ? Carbon::parse($row->fecha_lectura) : null;
        $row->medidor = (object) [
            'id_medidor' => $row->id_medidor,
            'numero_serie' => $row->numero_serie,
            'socio' => (object) [
                'id_socio' => $row->id_socio,
                'codigo_display' => $row->codigo_display ?: 'Sin socio',
                'persona' => (object) [
                    'nombre_completo' => $row->socio_nombre ?: 'Sin socio',
                ],
            ],
        ];
        $row->empleado = $row->id_empleado ? (object) [
            'id_empleado' => $row->id_empleado,
            'persona' => (object) [
                'nombre_completo' => $row->lector_nombre ?: 'Sin lector',
            ],
        ] : null;
        $row->evidencia_url = $row->evidencia_path
            ? route('private-media.reading-evidence', $row->id_lectura)
            : null;

        return $row;
    }

    public function create(): View
    {
        return view('lecturas.create', [
            'medidoresDisponibles' => $this->medidoresDisponibles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha_lectura' => ['required', 'date', 'before_or_equal:today'],
            'lectura_anterior' => ['nullable', 'numeric', 'min:0'],
            'lectura_actual' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'id_medidor' => ['required', 'integer', 'min:1'],
            'evidencia' => ['nullable', 'image', 'max:5120'],
            'estado_lectura' => ['nullable', 'in:normal,observada,requiere_verificacion'],
            'redirect_to' => ['nullable', 'in:tecnico.lecturas.index,tecnico.consumo.index'],
        ]);

        $meterContext = $this->meterContext((int) $data['id_medidor']);

        if (!$meterContext) {
            return back()->withInput()->withErrors([
                'id_medidor' => 'Selecciona un medidor activo valido para registrar la lectura.',
            ]);
        }

        $lecturaAnterior = (float) ($meterContext->lectura_anterior ?? 0);

        $data['lectura_anterior'] = $lecturaAnterior;
        $data['latitud'] = filled($data['latitud'] ?? null)
            ? $data['latitud']
            : ($meterContext->latitud ?? $meterContext->socio_latitud ?? null);
        $data['longitud'] = filled($data['longitud'] ?? null)
            ? $data['longitud']
            : ($meterContext->longitud ?? $meterContext->socio_longitud ?? null);

        if ((float) $data['lectura_actual'] < $lecturaAnterior) {
            return back()->withInput()->withErrors([
                'lectura_actual' => 'La lectura actual no puede ser menor a la registrada anteriormente para este medidor.',
            ])->with('reading_issue', [
                'current' => round((float) $data['lectura_actual'], 2),
                'previous' => round($lecturaAnterior, 2),
                'medidor_id' => (int) $data['id_medidor'],
            ]);
        }

        $duplicada = DB::table('lecturas')
            ->where('id_medidor', $data['id_medidor'])
            ->whereDate('fecha_lectura', $data['fecha_lectura'])
            ->exists();

        if ($duplicada) {
            return back()->withInput()->withErrors([
                'fecha_lectura' => 'Ya existe una lectura registrada para este medidor en la fecha seleccionada.',
            ]);
        }

        $observaciones = trim((string) ($data['observaciones'] ?? ''));

        if (!empty($data['estado_lectura']) && $data['estado_lectura'] !== 'normal') {
            $estadoLabel = str_replace('_', ' ', $data['estado_lectura']);
            $observaciones = trim("Estado de lectura: {$estadoLabel}. {$observaciones}");
        }

        unset($data['estado_lectura'], $data['redirect_to'], $data['evidencia']);

        $evidenciaPath = null;
        if ($request->hasFile('evidencia')) {
            $evidenciaPath = PrivateMedia::storeImage(
                $request->file('evidencia'),
                'lecturas/'.Carbon::parse($data['fecha_lectura'])->format('Y/m/d'),
                'medidor_'.$data['id_medidor']
            );

            if (! $evidenciaPath) {
                return back()->withInput()->withErrors([
                    'evidencia' => 'No se pudo guardar la foto. Intenta capturarla nuevamente.',
                ]);
            }
        }

        try {
            $lectura = Lectura::create($data + [
                'id_empleado' => $this->resolveEmpleadoId(),
                'observaciones' => $observaciones !== '' ? $observaciones : null,
                'evidencia_path' => $evidenciaPath,
            ]);
        } catch (Throwable $exception) {
            PrivateMedia::delete($evidenciaPath);
            throw $exception;
        }

        try {
            $lectura->loadMissing(['medidor.socio.persona', 'empleado.persona']);
            LecturaRegistrada::dispatch([
                'lectura_id' => (int) $lectura->id_lectura,
                'fecha_lectura' => $lectura->fecha_lectura?->format('Y-m-d') ?? $data['fecha_lectura'],
                'numero_serie' => $lectura->medidor?->numero_serie ?? '',
                'socio_nombre' => $lectura->medidor?->socio?->persona?->nombre_completo ?? 'Sin socio',
                'socio_codigo' => $lectura->medidor?->socio?->codigo_display ?? '-',
                'lectura_anterior' => (float) $lectura->lectura_anterior,
                'lectura_actual' => (float) $lectura->lectura_actual,
                'consumo_m3' => max(0, (float) $lectura->lectura_actual - (float) $lectura->lectura_anterior),
                'lector_nombre' => $lectura->empleado?->persona?->nombre_completo ?? 'Sin lector',
                'evidencia_url' => $lectura->evidencia_path
                    ? route('private-media.reading-evidence', $lectura->id_lectura)
                    : null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        OperationalCache::bumpDomain('billing');
        Cache::forget('lecturas:stats');
        Cache::forget('lecturas:medidores-disponibles');
        OperationalCache::forget('lecturas:stats:' . now()->format('Y-m'));
        OperationalCache::forget('lecturas:medidores-disponibles');
        OperationalCache::forget('lecturas:medidores-disponibles:limit:80');
        OperationalCache::forget('lecturas:medidores-disponibles-gps:limit:80');
        Cache::forget('tecnico:consumo:catalogo');
        Cache::forget('tecnico:consumo:catalogo:v2');
        Cache::forget('tecnico:consumo:catalogo:v3');
        Cache::forget('tecnico:consumo:stats');
        Cache::forget('tecnico:consumo:recent-readings');
        Cache::forget('dashboard:tecnico:upcoming-readings');
        Cache::forget('dashboard:tecnico:reading-calendar:' . now()->format('Y-m'));
        Cache::forget('api.dashboard.tecnico');
        Cache::add('lecturas:index:version', 1, now()->addYears(2));
        Cache::increment('lecturas:index:version');
        OperationalCache::forget('consumo:catalogo:legacy');
        OperationalCache::forget('consumo:stats:'.today()->toDateString());
        OperationalCache::forget('consumo:recent-readings');
        OperationalCache::forget('dashboard:tecnico:upcoming-readings');
        OperationalCache::forget('dashboard:tecnico:reading-calendar:'.now()->format('Y-m'));
        OperationalCache::forget('billing:signals');
        OperationalCache::forget('api-dashboard-tecnico');
        OperationalCache::forget('mapa-operativo:markers');
        Cache::add('tecnico:catalog:consumo:medidores:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores:version');
        Cache::add('tecnico:catalog:consumo:medidores-gps:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores-gps:version');

        $route = Auth::user()?->hasRole('administrador')
            ? 'tecnico.lecturas.index'
            : $request->input('redirect_to', 'tecnico.lecturas.index');
        $message = $route === 'tecnico.consumo.index'
            ? 'Consumo registrado correctamente.'
            : 'Lecturacion registrada correctamente.';

        return redirect()->route($route)->with('success', $message);
    }

    private function medidoresDisponibles(int $limit = 80)
    {
        return OperationalCache::remember('lecturas:medidores-disponibles-gps:limit:'.$limit, function () use ($limit) {
            return DB::table('v_tecnico_medidores_consumo')
                ->select([
                    'id_medidor',
                    'numero_serie',
                    'codigo_usuario',
                    'socio_nombre',
                    'zona',
                    'lectura_sugerida',
                    'ultima_fecha',
                    'medidor_latitud',
                    'medidor_longitud',
                    'socio_latitud',
                    'socio_longitud',
                ])
                ->orderBy('numero_serie')
                ->limit($limit)
                ->get()
                ->map(function (object $medidor) {
                    return (object) [
                        'id_medidor' => $medidor->id_medidor,
                        'numero_serie' => $medidor->numero_serie,
                        'socio_nombre' => $medidor->socio_nombre ?: 'Sin socio',
                        'codigo_usuario' => $medidor->codigo_usuario ?: '-',
                        'zona' => $medidor->zona ?: 'Sin zona',
                        'lectura_sugerida' => (float) ($medidor->lectura_sugerida ?? 0),
                        'ultima_fecha' => $medidor->ultima_fecha
                            ? Carbon::parse($medidor->ultima_fecha)->format('d/m/Y')
                            : null,
                        'latitud' => $medidor->medidor_latitud ?? $medidor->socio_latitud,
                        'longitud' => $medidor->medidor_longitud ?? $medidor->socio_longitud,
                    ];
                });
        });
    }

    private function meterContext(int $medidorId): ?object
    {
        $ultimaLecturaActual = DB::table('lecturas as l')
            ->select('l.lectura_actual')
            ->whereColumn('l.id_medidor', 'm.id_medidor')
            ->orderByDesc('l.fecha_lectura')
            ->orderByDesc('l.id_lectura')
            ->limit(1);

        return DB::table('medidores as m')
            ->select('m.id_medidor', 'm.latitud', 'm.longitud', 's.latitud as socio_latitud', 's.longitud as socio_longitud')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->selectSub($ultimaLecturaActual, 'lectura_anterior')
            ->where('m.id_medidor', $medidorId)
            ->where('m.estado', 'activo')
            ->first();
    }

    private function resolveEmpleadoId(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return Cache::remember('lecturas:empleado:user:'.$user->id, now()->addHours(12), function () use ($user) {
            $empleadoId = DB::table('empleados')
                ->where('id_persona', $user->id_persona)
                ->where('estado', 'activo')
                ->value('id_empleado');

            return $empleadoId ?: Empleado::query()
                ->where('estado', 'activo')
                ->orderBy('id_empleado')
                ->value('id_empleado');
        });
    }

    private function readingStats(): array
    {
        return OperationalCache::remember('lecturas:stats:' . now()->format('Y-m'), function () {
            $summary = Lectura::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE fecha_lectura BETWEEN ? AND ?) as mes,
                    AVG(consumo_m3) as promedio_consumo
                ", [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'mes' => (int) ($summary?->mes ?? 0),
                'promedio_consumo' => round((float) ($summary?->promedio_consumo ?? 0), 2),
            ];
        });
    }
}
