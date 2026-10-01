<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\IngresoAdministrativo;
use App\Models\Medidor;
use App\Models\Notificacion;
use App\Models\OrdenTecnica;
use App\Models\Persona;
use App\Models\Sector;
use App\Models\Socio;
use App\Models\SystemSetting;
use App\Models\Tarifa;
use App\Support\OperationalCache;
use App\Support\PrivateMedia;
use App\Support\SequentialMedidorNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SocioController extends Controller
{
    public function index(Request $request)
    {
        return view('socios.index', [
            'socios' => $this->socioPaginator($request),
            'sectores' => $this->sectorFilterOptions(),
        ]);
    }

    public function warmIndexCache(): void
    {
        $request = Request::create('/admin/socios', 'GET');

        $this->socioPaginator($request, url('/admin/socios'));
        $this->sectorFilterOptions();
        $this->sectorFormOptions();
        $this->tariffFormOptions();
    }

    private function socioPaginator(Request $request, ?string $path = null)
    {
        $hasOcultoColumn = Cache::remember('schema.socios.oculto', now()->addHours(12), function () {
            return Schema::hasColumn('socios', 'oculto');
        });

        $query = DB::table('socios as s')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->leftJoin('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
            ->leftJoin('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->select([
                's.id_socio',
                's.numero_socio',
                's.direccion',
                's.estado',
                'p.cedula_identidad',
                'p.telefono',
                'sec.nombre as sector_nombre',
                'sec.zona as sector_zona',
                't.nombre as tarifa_nombre',
                'm.numero_serie as medidor_numero_serie',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->selectRaw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo_display")
            ->selectRaw($hasOcultoColumn ? 's.oculto, s.motivo_ocultacion' : 'false as oculto, NULL::text as motivo_ocultacion')
            ->orderByDesc('s.created_at');

        if ($request->filled('buscar') && strlen(trim((string) $request->buscar)) >= 2) {
            $term = trim((string) $request->buscar);

            $query->where(function ($builder) use ($term) {
                $builder->where('numero_socio', 'ilike', "%{$term}%")
                    ->orWhere('direccion', 'ilike', "%{$term}%")
                    ->orWhere('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%")
                    ->orWhere('sec.nombre', 'ilike', "%{$term}%")
                    ->orWhere('t.nombre', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('s.estado', $request->estado);
        }

        if ($request->filled('sector')) {
            $query->where('s.id_sector', $request->sector);
        }

        if ($hasOcultoColumn && $request->filled('visibilidad')) {
            $query->where('s.oculto', $request->visibilidad === 'ocultos');
        }

        $cacheKey = 'socios.index.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('operations', 'socios.index.'.$cacheKey, fn () => $query
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/socios'))
            ->appends($request->query()));
    }

    private function sectorFilterOptions()
    {
        return OperationalCache::rememberDomain('operations', 'socios.sectores', function () {
            return Sector::select('id_sector', 'nombre')
                ->orderBy('nombre')
                ->get();
        });
    }

    private function sectorFormOptions()
    {
        return OperationalCache::rememberDomain('operations', 'socios.form.sectores', fn () => Sector::query()
            ->select(['id_sector', 'nombre', 'zona'])
            ->orderBy('nombre')
            ->get());
    }

    private function tariffFormOptions()
    {
        return OperationalCache::rememberDomain('billing', 'socios.form.tarifas', fn () => Tarifa::query()
            ->select(['id_tarifa', 'nombre', 'tipo_uso', 'estado'])
            ->orderBy('nombre')
            ->get());
    }

    public function create()
    {
        return view('socios.create', [
            'sectores' => $this->sectorFormOptions(),
            'tarifas' => $this->tariffFormOptions(),
            'nextNumeroMedidor' => SequentialMedidorNumber::next(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSocio($request);

        $socio = DB::transaction(function () use ($request, $data) {
            $persona = Persona::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'foto_path' => $this->storeSocioPhoto($request),
            ]);

            $socio = Socio::create([
                'numero_socio' => $this->nextNumeroSocio(),
                'direccion' => $data['direccion'] ?? null,
                'latitud' => $data['latitud'] ?? null,
                'longitud' => $data['longitud'] ?? null,
                'fecha_registro' => now()->toDateString(),
                'estado' => 'suspendido',
                'id_persona' => $persona->id_persona,
                'id_sector' => $data['id_sector'],
                'id_tarifa' => $data['id_tarifa'],
            ]);

            $medidor = Medidor::create([
                'numero_serie' => $data['numero_serie'],
                'fecha_instalacion' => null,
                'latitud' => $data['medidor_latitud'] ?? ($data['latitud'] ?? null),
                'longitud' => $data['medidor_longitud'] ?? ($data['longitud'] ?? null),
                'estado' => 'inactivo',
                'id_socio' => $socio->id_socio,
            ]);

            OrdenTecnica::create([
                'tipo' => 'instalacion',
                'estado' => 'pendiente',
                'prioridad' => 'media',
                'fecha_programada' => now()->addDay()->toDateString(),
                'zona' => $socio->sector?->nombre,
                'referencia' => 'Alta de socio '.$socio->numero_socio,
                'descripcion' => 'Instalacion pendiente para activar socio. Direccion: '.($socio->direccion ?: 'sin direccion registrada').'.',
                'coord_x' => $medidor->latitud ?? $socio->latitud,
                'coord_y' => $medidor->longitud ?? $socio->longitud,
                'id_socio' => $socio->id_socio,
                'id_medidor' => $medidor->id_medidor,
                'id_empleado' => $this->resolveEmpleadoId(),
            ]);

            Notificacion::create([
                'tipo' => 'instalacion_pendiente',
                'mensaje' => 'Nuevo socio '.$socio->numero_socio.' registrado. Requiere instalacion de medidor antes de activar facturacion.',
                'fecha_envio' => now(),
                'enviado' => false,
                'canal' => 'sistema',
                'id_socio' => $socio->id_socio,
            ]);

            return $socio;
        });

        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio registrado como pendiente de instalacion. El tecnico debe confirmar el medidor antes de facturar.');
    }

    public function show(Socio $socio)
    {
        $socio = $this->cachedSocioDetail($socio);

        return view('socios.show', compact('socio'));
    }

    public function carnet(Socio $socio)
    {
        $socio = $this->cachedSocioDetail($socio);
        $carnetSettings = $this->carnetSettings();
        $ingresosCarnet = OperationalCache::remember(
            "socio:carnet-ingresos:{$socio->id_socio}",
            fn () => IngresoAdministrativo::query()
                ->with('empleado.persona')
                ->where('id_socio', $socio->id_socio)
                ->where('categoria', 'Carnetizacion')
                ->orderByDesc('fecha_ingreso')
                ->orderByDesc('id_ingreso')
                ->limit(5)
                ->get(),
            now()->addHours(6)
        );

        return view('socios.carnet', compact('socio', 'carnetSettings', 'ingresosCarnet'));
    }

    public function registrarCarnetizacion(Request $request, Socio $socio)
    {
        $socio->loadMissing('persona');
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        IngresoAdministrativo::create([
            'fecha_ingreso' => now()->toDateString(),
            'concepto' => 'Carnetizacion de socio',
            'categoria' => 'Carnetizacion',
            'descripcion' => $data['descripcion']
                ?: 'Emision de carnet para '.($socio->persona?->nombre_completo ?? $socio->codigo_display),
            'monto' => round((float) $data['monto'], 2),
            'id_socio' => $socio->id_socio,
            'id_empleado' => $this->resolveEmpleadoId(),
        ]);

        Cache::forget('dashboard.secretaria.stats');
        Cache::forget('dashboard.secretaria.payments.recent');
        Cache::forget('api.dashboard.secretaria');
        OperationalCache::forget("socio:carnet-ingresos:{$socio->id_socio}");
        OperationalCache::forget("socio:detail:{$socio->id_socio}");

        return redirect()
            ->route('admin.socios.carnet', $socio)
            ->with('success', 'Ingreso por carnetizacion registrado correctamente.');
    }

    public function edit(Socio $socio)
    {
        $socio->load(['persona', 'medidorActivo']);

        return view('socios.edit', [
            'socio' => $socio,
            'sectores' => $this->sectorFormOptions(),
            'tarifas' => $this->tariffFormOptions(),
        ]);
    }

    public function update(Request $request, Socio $socio)
    {
        $data = $this->validateSocio($request, $socio);

        DB::transaction(function () use ($request, $socio, $data): void {
            $fotoPath = $this->storeSocioPhoto($request, $socio->persona->foto_path);

            $socio->persona->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cedula_identidad' => $data['cedula_identidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'foto_path' => $fotoPath,
            ]);

            $socio->update([
                'direccion' => $data['direccion'] ?? null,
                'latitud' => $data['latitud'] ?? null,
                'longitud' => $data['longitud'] ?? null,
                'estado' => $data['estado'],
                'id_sector' => $data['id_sector'],
                'id_tarifa' => $data['id_tarifa'],
            ]);

            $medidor = $socio->medidorActivo()->first();

            if ($medidor) {
                $medidor->update([
                    'numero_serie' => $data['numero_serie'],
                    'fecha_instalacion' => $data['fecha_instalacion'] ?? null,
                    'latitud' => $data['medidor_latitud'] ?? ($data['latitud'] ?? null),
                    'longitud' => $data['medidor_longitud'] ?? ($data['longitud'] ?? null),
                ]);
            } else {
                Medidor::create([
                    'numero_serie' => $data['numero_serie'],
                    'fecha_instalacion' => $data['fecha_instalacion'] ?? now()->toDateString(),
                    'latitud' => $data['medidor_latitud'] ?? ($data['latitud'] ?? null),
                    'longitud' => $data['medidor_longitud'] ?? ($data['longitud'] ?? null),
                    'estado' => 'activo',
                    'id_socio' => $socio->id_socio,
                ]);
            }
        });

        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio actualizado correctamente.');
    }

    public function hide(Request $request, Socio $socio)
    {
        $request->validate([
            'motivo_ocultacion' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $socio->update([
            'oculto' => true,
            'motivo_ocultacion' => $request->motivo_ocultacion,
            'oculto_en' => now(),
            'oculto_por' => Auth::id(),
            'estado' => 'inactivo',
        ]);
        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio ocultado correctamente con registro de auditoria.');
    }

    public function unhide(Socio $socio)
    {
        $socio->update([
            'oculto' => false,
            'motivo_ocultacion' => null,
            'oculto_en' => null,
            'oculto_por' => null,
            'estado' => 'activo',
        ]);
        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio restaurado correctamente.');
    }

    public function activate(Socio $socio)
    {
        if (! $socio->medidores()->where('estado', 'activo')->exists()) {
            return redirect()
                ->route('admin.socios.index', request()->query())
                ->with('error', 'No se puede activar el socio hasta que el tecnico confirme un medidor activo.');
        }

        $socio->update([
            'estado' => 'activo',
        ]);
        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index', request()->query())
            ->with('success', 'Socio activado correctamente.');
    }

    public function deactivate(Socio $socio)
    {
        $socio->update([
            'estado' => 'inactivo',
        ]);
        $this->flushOperationalCaches((int) $socio->id_socio);

        return redirect()
            ->route('admin.socios.index', request()->query())
            ->with('success', 'Socio marcado como inactivo.');
    }

    private function validateSocio(Request $request, ?Socio $socio = null): array
    {
        $personaId = $socio?->persona?->id_persona;
        $medidorId = $socio?->medidorActivo?->id_medidor;

        if (! $socio && ! $request->filled('numero_serie')) {
            $request->merge(['numero_serie' => SequentialMedidorNumber::next()]);
        }

        return $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'cedula_identidad' => [
                'required',
                'string',
                'max:30',
                Rule::unique('personas', 'cedula_identidad')->ignore($personaId, 'id_persona'),
            ],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('personas', 'email')->ignore($personaId, 'id_persona'),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'suspendido', 'cortado'])],
            'id_sector' => ['required', 'exists:sectores,id_sector'],
            'id_tarifa' => ['required', 'exists:tarifas,id_tarifa'],
            'numero_serie' => [
                'required',
                'string',
                'max:60',
                Rule::unique('medidores', 'numero_serie')->ignore($medidorId, 'id_medidor'),
            ],
            'fecha_instalacion' => ['nullable', 'date'],
            'medidor_latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'medidor_longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }

    private function nextNumeroSocio(): string
    {
        $next = ((int) Socio::max('id_socio')) + 1;

        return 'SOC-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function storeSocioPhoto(Request $request, ?string $currentPath = null): ?string
    {
        if (! $request->hasFile('foto')) {
            return $currentPath;
        }

        $file = $request->file('foto');
        if (! $file->isValid()) {
            return $currentPath;
        }

        return PrivateMedia::storeImage($file, 'socios', 'socio', $currentPath);
    }

    private function carnetSettings(): array
    {
        $general = SystemSetting::getValue('general', []);

        return [
            'carnet_back_text' => $general['carnet_back_text'] ?? 'Este carnet identifica al socio registrado en EPSAS. En caso de extravio, comuniquese con administracion para su reposicion.',
            'carnet_fee' => (float) ($general['carnet_fee'] ?? 10),
            'company_logo' => $general['company_logo'] ?? null,
            'company_name' => $general['company_name'] ?? 'EPSAS',
            'company_alias' => $general['company_alias'] ?? 'Servicio de agua potable',
        ];
    }

    private function cachedSocioDetail(Socio $socio): Socio
    {
        return OperationalCache::remember(
            "socio:detail:{$socio->id_socio}",
            function () use ($socio): Socio {
                $loaded = Socio::query()
                    ->with(['persona', 'sector', 'tarifa', 'medidores'])
                    ->findOrFail($socio->id_socio);
                $loaded->setRelation('medidorActivo', $loaded->medidores->firstWhere('estado', 'activo'));

                return $loaded;
            },
            now()->addHours(6)
        );
    }

    private function resolveEmpleadoId(): ?int
    {
        return Auth::user()?->persona?->empleado?->id_empleado
            ?? Empleado::query()->where('estado', 'activo')->orderBy('id_empleado')->value('id_empleado');
    }

    private function flushOperationalCaches(?int $socioId = null): void
    {
        OperationalCache::bumpDomain('operations');
        OperationalCache::bumpDomain('billing');
        Cache::forget('tecnico:socios:catalogo');
        Cache::forget('tecnico:socios:catalogo:v2');
        Cache::forget('tecnico:consumo:catalogo:v3');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        OperationalCache::forget('mapa-operativo:markers');
        Cache::forget('medidores:socios-disponibles');
        Cache::forget('medidores:socios-disponibles:v2');
        Cache::forget('medidores:socios-disponibles:v3');
        SequentialMedidorNumber::forgetCache();
        Cache::add('tarifas:index:version', 1, now()->addYears(2));
        Cache::increment('tarifas:index:version');
        Cache::forget('medidores:stats');
        Cache::add('medidores:index:version', 1, now()->addYears(2));
        Cache::increment('medidores:index:version');
        Cache::add('tecnico:catalog:consumo:medidores-gps:version', 1, now()->addYears(2));
        Cache::increment('tecnico:catalog:consumo:medidores-gps:version');
        if ($socioId) {
            OperationalCache::forget("socio:detail:{$socioId}");
            OperationalCache::forget("socio:carnet-ingresos:{$socioId}");
            OperationalCache::forget("cobros:socio-detail:v2:{$socioId}");
            OperationalCache::forget("route-binding:App.Models.Socio:id_socio:{$socioId}");
        }

        OperationalCache::forget('socios:catalogo:limit:50');
        OperationalCache::forget('socios:catalogo:limit:60');
        OperationalCache::forget('socios:catalogo:v2:limit:50');
        OperationalCache::forget('socios:catalogo:v2:limit:60');
        OperationalCache::forget('orders:instalacion:summary');
        OperationalCache::forget('consumo:catalogo:legacy');
        OperationalCache::forget('lecturas:medidores-disponibles-gps:limit:80');
        OperationalCache::forget('billing:signals');
        OperationalCache::forget('corte:open-socios');
        OperationalCache::forget('reconexion:open-socios');
        OperationalCache::forget('reconexion:latest-cuts');
        OperationalCache::forget('api-dashboard-tecnico');
    }
}
