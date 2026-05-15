<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\Http\Request;

class TarifaController extends Controller
{
    public function index(Request $request)
    {
        $query = Tarifa::query()->withCount('socios')->orderByDesc('fecha_vigencia')->orderBy('nombre');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('nombre', 'ilike', "%{$term}%")
                    ->orWhere('estado', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return view('tarifas.index', [
            'tarifas' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('tarifas.create');
    }

    public function store(Request $request)
    {
        $tarifa = Tarifa::create($this->validateTarifa($request));

        return redirect()
            ->route('admin.tarifas.edit', $tarifa)
            ->with('success', 'Tarifa registrada correctamente.');
    }

    public function edit(Tarifa $tarifa)
    {
        return view('tarifas.edit', [
            'tarifa' => $tarifa,
        ]);
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $tarifa->update($this->validateTarifa($request));

        return redirect()
            ->route('admin.tarifas.edit', $tarifa)
            ->with('success', 'Tarifa actualizada correctamente.');
    }

    private function validateTarifa(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'tipo_uso' => ['required', 'in:domestico,comercial'],
            'precio_m3_base' => ['required', 'numeric', 'min:0'],
            'consumo_minimo_m3' => ['required', 'numeric', 'min:0'],
            'cargo_fijo' => ['required', 'numeric', 'min:0'],
            'fecha_vigencia' => ['required', 'date'],
            'estado' => ['required', 'in:activa,inactiva'],
        ]);
    }
}
