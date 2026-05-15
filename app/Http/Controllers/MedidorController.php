<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Medidor;
use App\Models\Socio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedidorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Medidor::query()
            ->with(['socio.persona', 'socio.sector', 'empleadoInstalador.persona'])
            ->orderByDesc('created_at');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);

            $query->where(function ($builder) use ($term) {
                $builder->where('numero_serie', 'ilike', "%{$term}%")
                    ->orWhere('marca', 'ilike', "%{$term}%")
                    ->orWhere('modelo', 'ilike', "%{$term}%")
                    ->orWhereHas('socio.persona', function ($relation) use ($term) {
                        $relation->where('nombres', 'ilike', "%{$term}%")
                            ->orWhere('apellidos', 'ilike', "%{$term}%")
                            ->orWhere('cedula_identidad', 'ilike', "%{$term}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return view('medidores.index', [
            'medidores' => $query->paginate(12)->withQueryString(),
            'stats' => [
                'total' => Medidor::count(),
                'activos' => Medidor::where('estado', 'activo')->count(),
                'danados' => Medidor::where('estado', 'danado')->count(),
                'reemplazados' => Medidor::where('estado', 'reemplazado')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('medidores.create', [
            'sociosDisponibles' => $this->availableSocios(),
            'tecnicos' => $this->tecnicos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMedidor($request);

        if ($data['estado'] === 'activo') {
            $medidorActivo = Medidor::query()
                ->where('id_socio', $data['id_socio'])
                ->where('estado', 'activo')
                ->first();

            if ($medidorActivo) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'id_socio' => "El socio ya tiene un medidor activo ({$medidorActivo->numero_serie}).",
                    ]);
            }
        }

        Medidor::create($data);

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor registrado correctamente.');
    }

    public function edit(Medidor $medidor): View
    {
        $medidor->load(['socio.persona', 'empleadoInstalador.persona']);

        return view('medidores.edit', [
            'medidor' => $medidor,
            'tecnicos' => $this->tecnicos(),
        ]);
    }

    public function update(Request $request, Medidor $medidor): RedirectResponse
    {
        $data = $this->validateMedidor($request, $medidor);

        if ($data['estado'] === 'activo') {
            $medidorActivo = Medidor::query()
                ->where('id_socio', $data['id_socio'])
                ->where('estado', 'activo')
                ->where('id_medidor', '!=', $medidor->id_medidor)
                ->first();

            if ($medidorActivo) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'id_socio' => "El socio ya tiene un medidor activo ({$medidorActivo->numero_serie}).",
                    ]);
            }
        }

        $medidor->update($data);

        return redirect()
            ->route('tecnico.medidores.index')
            ->with('success', 'Medidor actualizado correctamente.');
    }

    private function validateMedidor(Request $request, ?Medidor $medidor = null): array
    {
        return $request->validate([
            'numero_serie' => [
                'required',
                'string',
                'max:60',
                Rule::unique('medidores', 'numero_serie')->ignore($medidor?->id_medidor, 'id_medidor'),
            ],
            'marca' => ['required', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'fecha_instalacion' => ['required', 'date'],
            'estado' => ['required', Rule::in(['activo', 'inactivo', 'danado', 'reemplazado'])],
            'id_socio' => ['required', 'integer', 'exists:socios,id_socio'],
            'id_empleado_instalador' => ['nullable', 'integer', 'exists:empleados,id_empleado'],
        ]);
    }

    private function tecnicos()
    {
        return Empleado::query()
            ->with('persona')
            ->where('estado', 'activo')
            ->whereHas('rol', fn ($rol) => $rol->where('nombre', 'tecnico'))
            ->orderBy('id_empleado')
            ->get();
    }

    private function availableSocios()
    {
        return Socio::query()
            ->with('persona')
            ->whereDoesntHave('medidorActivo')
            ->orderBy('numero_socio')
            ->get();
    }
}
