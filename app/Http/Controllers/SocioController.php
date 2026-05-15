<?php

namespace App\Http\Controllers;

use App\Models\Medidor;
use App\Models\Persona;
use App\Models\Sector;
use App\Models\Socio;
use App\Models\Tarifa;
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

        if ($request->filled('buscar')) {
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

        return view('socios.index', [
            'socios' => $query->simplePaginate(12)->withQueryString(),
            'sectores' => Cache::remember('socios.sectores', now()->addMinutes(10), function () {
                return Sector::select('id_sector', 'nombre')
                    ->orderBy('nombre')
                    ->get();
            }),
        ]);
    }

    public function create()
    {
        return view('socios.create', [
            'sectores' => Sector::orderBy('nombre')->get(),
            'tarifas' => Tarifa::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSocio($request);

        $persona = Persona::create([
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'cedula_identidad' => $data['cedula_identidad'],
            'telefono' => $data['telefono'],
            'email' => $data['email'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
        ]);

        $socio = Socio::create([
            'numero_socio' => $this->nextNumeroSocio(),
            'direccion' => $data['direccion'] ?? null,
            'fecha_registro' => now()->toDateString(),
            'estado' => $data['estado'],
            'id_persona' => $persona->id_persona,
            'id_sector' => $data['id_sector'],
            'id_tarifa' => $data['id_tarifa'],
        ]);

        Medidor::create([
            'numero_serie' => $data['numero_serie'],
            'fecha_instalacion' => $data['fecha_instalacion'] ?? now()->toDateString(),
            'estado' => 'activo',
            'id_socio' => $socio->id_socio,
        ]);

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio registrado correctamente.');
    }

    public function show(Socio $socio)
    {
        $socio->load(['persona', 'sector', 'tarifa', 'medidores']);

        return view('socios.show', compact('socio'));
    }

    public function edit(Socio $socio)
    {
        $socio->load(['persona', 'medidorActivo']);

        return view('socios.edit', [
            'socio' => $socio,
            'sectores' => Sector::orderBy('nombre')->get(),
            'tarifas' => Tarifa::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Socio $socio)
    {
        $data = $this->validateSocio($request, $socio);

        $socio->persona->update([
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'cedula_identidad' => $data['cedula_identidad'],
            'telefono' => $data['telefono'],
            'email' => $data['email'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
        ]);

        $socio->update([
            'direccion' => $data['direccion'] ?? null,
            'estado' => $data['estado'],
            'id_sector' => $data['id_sector'],
            'id_tarifa' => $data['id_tarifa'],
        ]);

        $medidor = $socio->medidorActivo()->first();

        if ($medidor) {
            $medidor->update([
                'numero_serie' => $data['numero_serie'],
                'fecha_instalacion' => $data['fecha_instalacion'] ?? null,
            ]);
        } else {
            Medidor::create([
                'numero_serie' => $data['numero_serie'],
                'fecha_instalacion' => $data['fecha_instalacion'] ?? now()->toDateString(),
                'estado' => 'activo',
                'id_socio' => $socio->id_socio,
            ]);
        }

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

        return redirect()
            ->route('admin.socios.index')
            ->with('success', 'Socio restaurado correctamente.');
    }

    public function activate(Socio $socio)
    {
        $socio->update([
            'estado' => 'activo',
        ]);

        return redirect()
            ->route('admin.socios.index', request()->query())
            ->with('success', 'Socio activado correctamente.');
    }

    public function deactivate(Socio $socio)
    {
        $socio->update([
            'estado' => 'inactivo',
        ]);

        return redirect()
            ->route('admin.socios.index', request()->query())
            ->with('success', 'Socio marcado como inactivo.');
    }

    private function validateSocio(Request $request, ?Socio $socio = null): array
    {
        $personaId = $socio?->persona?->id_persona;
        $medidorId = $socio?->medidorActivo?->id_medidor;

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
            'email' => ['nullable', 'email', 'max:150'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'direccion' => ['nullable', 'string', 'max:255'],
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
        ]);
    }

    private function nextNumeroSocio(): string
    {
        $next = ((int) Socio::max('id_socio')) + 1;

        return 'SOC-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
