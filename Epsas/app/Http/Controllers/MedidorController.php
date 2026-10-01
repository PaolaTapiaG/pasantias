<?php

namespace App\Http\Controllers;

use App\Models\Medidor;
use App\Support\OperationalCache;
use App\Support\SequentialMedidorNumber;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedidorController extends Controller
{
    public function index(Request $request): View
    {
        return view('medidores.index', [
            'medidores' => $this->meterPaginator($request),
            'stats' => $this->meterStats(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/medidores', 'GET');

        $this->meterPaginator($request, url('/admin/medidores'));
        $this->meterStats();
        $this->availableSocios();
        $this->tecnicos();
    }

    private function meterPaginator(Request $request, ?string $path = null)
    {
        $query = DB::table('medidores as m')
            ->leftJoin('socios as s', 's.id_socio', '=', 'm.id_socio')
            ->leftJoin('personas as ps', 'ps.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'm.id_empleado_instalador')
            ->leftJoin('personas as pe', 'pe.id_persona', '=', 'e.id_persona')
            ->select([
                'm.id_medidor',
                'm.numero_serie',
                'm.marca',
                'm.modelo',
                'm.fecha_instalacion',
                'm.estado',
                'm.id_socio',
                'm.id_empleado_instalador',
                'm.created_at',
                's.numero_socio',
                'sec.nombre as sector_nombre',
                'ps.cedula_identidad',
            ])
            ->selectRaw("TRIM(COALESCE(ps.nombres, '') || ' ' || COALESCE(ps.apellidos, '')) as socio_nombre")
            ->selectRaw("TRIM(COALESCE(pe.nombres, '') || ' ' || COALESCE(pe.apellidos, '')) as instalador_nombre")
            ->orderByDesc('m.created_at');

        if ($request->filled('buscar') && strlen(trim((string) $request->buscar)) >= 2) {
            $term = trim((string) $request->buscar);

            $query->where(function ($builder) use ($term) {
                $builder->where('m.numero_serie', 'ilike', "%{$term}%")
                    ->orWhere('m.marca', 'ilike', "%{$term}%")
                    ->orWhere('m.modelo', 'ilike', "%{$term}%")
                    ->orWhere('ps.nombres', 'ilike', "%{$term}%")
                    ->orWhere('ps.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('ps.cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('m.estado', $request->estado);
        }

        $cacheKey = 'medidores.index.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('operations', $cacheKey, fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/medidores'))
            ->appends($request->query())
            ->through(fn ($row) => $this->meterRowForView($row)));
    }

    private function meterRowForView(object $row): object
    {
        $row->fecha_instalacion = $row->fecha_instalacion ? Carbon::parse($row->fecha_instalacion) : null;
        $row->socio = $row->id_socio ? (object) [
            'id_socio' => $row->id_socio,
            'codigo_display' => $row->numero_socio ?: ('SOC-' . str_pad((string) $row->id_socio, 4, '0', STR_PAD_LEFT)),
            'persona' => (object) [
                'nombre_completo' => $row->socio_nombre ?: 'Sin socio',
            ],
            'sector' => (object) [
                'nombre' => $row->sector_nombre ?: 'Sin sector',
            ],
        ] : null;
        $row->empleadoInstalador = $row->id_empleado_instalador ? (object) [
            'id_empleado' => $row->id_empleado_instalador,
            'persona' => (object) [
                'nombre_completo' => $row->instalador_nombre ?: 'No asignado',
            ],
        ] : null;

        return $row;
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('medidores.create', [
            'sociosDisponibles' => $this->availableSocios(),
            'tecnicos' => $this->tecnicos(),
            'nextNumeroMedidor' => SequentialMedidorNumber::next(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $this->validateMedidor($request);

        Medidor::create($data);
        $this->flushMeterCaches();

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor registrado correctamente.');
    }

    public function edit(Medidor $medidor): View
    {
        $this->ensureAdmin();

        $medidor->load(['socio.persona', 'empleadoInstalador.persona']);

        return view('medidores.edit', [
            'medidor' => $medidor,
            'tecnicos' => $this->tecnicos(),
        ]);
    }

    public function update(Request $request, Medidor $medidor): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $this->validateMedidor($request, $medidor);

        $medidor->update($data);
        $this->flushMeterCaches();

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor actualizado correctamente.');
    }

    private function validateMedidor(Request $request, ?Medidor $medidor = null): array
    {
        if (! $medidor && ! $request->filled('numero_serie')) {
            $request->merge(['numero_serie' => SequentialMedidorNumber::next()]);
        }

        $data = $request->validate([
            'numero_serie' => ['required', 'string', 'max:60'],
            'marca' => ['required', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'fecha_instalacion' => ['required', 'date'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'danado', 'reemplazado'])],
            'id_socio' => ['required', 'integer'],
            'id_empleado_instalador' => ['nullable', 'integer'],
        ]);

        $this->validateMeterAvailability($data, $medidor);

        return $data;
    }

    private function validateMeterAvailability(array $data, ?Medidor $medidor = null): void
    {
        $messages = [];
        $socioId = (int) $data['id_socio'];
        $installerId = isset($data['id_empleado_instalador']) ? (int) $data['id_empleado_instalador'] : null;

        $socioAllowed = $medidor && (int) $medidor->id_socio === $socioId
            ? true
            : $this->availableSocios()->contains(fn ($row) => (int) $row->id_socio === $socioId);

        if (! $socioAllowed) {
            $messages['id_socio'] = 'Selecciona un socio disponible y sin medidor activo.';
        }

        if ($installerId && ! $this->tecnicos()->contains(fn ($row) => (int) $row->id_empleado === $installerId)) {
            $messages['id_empleado_instalador'] = 'Selecciona un tecnico activo.';
        }

        $conflicts = Medidor::query()
            ->select(['id_medidor', 'numero_serie', 'estado', 'id_socio'])
            ->when($medidor, fn ($query) => $query->where('id_medidor', '!=', $medidor->id_medidor))
            ->where(function ($query) use ($data, $socioId) {
                $query->where('numero_serie', $data['numero_serie'])
                    ->when($data['estado'] === 'activo', function ($query) use ($socioId) {
                        $query->orWhere(function ($query) use ($socioId) {
                            $query->where('id_socio', $socioId)
                                ->where('estado', 'activo');
                        });
                    });
            })
            ->limit(2)
            ->get();

        foreach ($conflicts as $conflict) {
            if ($conflict->numero_serie === $data['numero_serie']) {
                $messages['numero_serie'] = 'El numero de serie ya esta registrado.';
            }

            if ((int) $conflict->id_socio === $socioId && $conflict->estado === 'activo' && $data['estado'] === 'activo') {
                $messages['id_socio'] = "El socio ya tiene un medidor activo ({$conflict->numero_serie}).";
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function tecnicos()
    {
        return Cache::remember('medidores:tecnicos:v2', now()->addDay(), function () {
            return DB::table('empleados as e')
                ->join('personas as p', 'p.id_persona', '=', 'e.id_persona')
                ->join('roles as r', 'r.id_rol', '=', 'e.id_rol')
                ->where('e.estado', 'activo')
                ->whereRaw('LOWER(r.nombre) = ?', ['tecnico'])
                ->orderBy('p.nombres')
                ->orderBy('p.apellidos')
                ->get([
                    'e.id_empleado',
                    'e.id_persona',
                    DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
                ])
                ->map(function ($row) {
                    $row->persona = (object) [
                        'nombre_completo' => $row->nombre_completo ?: ('Tecnico #'.$row->id_empleado),
                    ];

                    return $row;
                });
        });
    }

    private function availableSocios()
    {
        return Cache::remember('medidores:socios-disponibles:v3', now()->addDay(), function () {
            return DB::table('socios as s')
                ->join('personas as p', 'p.id_persona', '=', 's.id_persona')
                ->leftJoin('medidores as m', function ($join) {
                    $join->on('m.id_socio', '=', 's.id_socio')
                        ->where('m.estado', '=', 'activo');
                })
                ->whereNull('m.id_medidor')
                ->orderBy('s.numero_socio')
                ->get([
                    's.id_socio',
                    's.numero_socio',
                    's.id_persona',
                    's.latitud',
                    's.longitud',
                    'p.cedula_identidad',
                    DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo"),
                ])
                ->map(function ($row) {
                    $row->codigo_display = $row->numero_socio ?: ('SOC-'.str_pad((string) $row->id_socio, 4, '0', STR_PAD_LEFT));
                    $row->persona = (object) [
                        'nombre_completo' => $row->nombre_completo ?: 'Sin nombre',
                        'cedula_identidad' => $row->cedula_identidad,
                    ];

                    return $row;
                });
        });
    }

    private function meterStats(): array
    {
        return OperationalCache::rememberDomain('operations', 'medidores.stats', function () {
            $summary = Medidor::query()
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE estado = 'activo') as activos,
                    COUNT(*) FILTER (WHERE estado = 'danado') as danados,
                    COUNT(*) FILTER (WHERE estado = 'reemplazado') as reemplazados
                ")
                ->first();

            return [
                'total' => (int) ($summary?->total ?? 0),
                'activos' => (int) ($summary?->activos ?? 0),
                'danados' => (int) ($summary?->danados ?? 0),
                'reemplazados' => (int) ($summary?->reemplazados ?? 0),
            ];
        });
    }

    private function flushMeterCaches(): void
    {
        OperationalCache::bumpDomain('operations');
        Cache::forget('medidores:stats');
        Cache::forget('medidores:tecnicos');
        Cache::forget('medidores:tecnicos:v2');
        Cache::forget('medidores:socios-disponibles');
        Cache::forget('medidores:socios-disponibles:v2');
        Cache::forget('medidores:socios-disponibles:v3');
        SequentialMedidorNumber::forgetCache();
        Cache::forget('lecturas:medidores-disponibles');
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
        Cache::forget('dashboard:tecnico:medidores-total');
        Cache::forget('api.dashboard.tecnico');
        Cache::add('medidores:index:version', 1, now()->addYears(2));
        Cache::increment('medidores:index:version');
        OperationalCache::forget('consumo:catalogo:legacy');
        OperationalCache::forget('consumo:stats:'.today()->toDateString());
        OperationalCache::forget('api-dashboard-tecnico');
        OperationalCache::forget('mapa-operativo:markers');
        Cache::add('tecnico:catalog:consumo:medidores:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores:version');
        Cache::add('tecnico:catalog:consumo:medidores-gps:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores-gps:version');
    }

    private function ensureAdmin(): void
    {
        if (!Auth::user()?->hasRole('administrador')) {
            throw new AuthorizationException('Solo el administrador puede modificar medidores.');
        }
    }
}
