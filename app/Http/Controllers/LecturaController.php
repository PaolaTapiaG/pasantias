<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Lectura;
use App\Models\Medidor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LecturaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lectura::query()
            ->with(['medidor.socio.persona', 'empleado.persona'])
            ->orderByDesc('fecha_lectura')
            ->orderByDesc('id_lectura');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->whereHas('medidor', function ($medidorQuery) use ($term) {
                    $medidorQuery->where('numero_serie', 'ilike', "%{$term}%")
                        ->orWhereHas('socio.persona', function ($personaQuery) use ($term) {
                            $personaQuery->where('nombres', 'ilike', "%{$term}%")
                                ->orWhere('apellidos', 'ilike', "%{$term}%")
                                ->orWhere('cedula_identidad', 'ilike', "%{$term}%");
                        });
                });
            });
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_lectura', [$request->desde, $request->hasta]);
        }

        return view('lecturas.index', [
            'lecturas' => $query->paginate(12)->withQueryString(),
            'stats' => [
                'total' => Lectura::count(),
                'mes' => Lectura::whereBetween('fecha_lectura', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count(),
                'promedio_consumo' => round((float) Lectura::avg('consumo_m3'), 2),
            ],
        ]);
    }

    public function create(): View
    {
        return view('lecturas.create', [
            'medidoresDisponibles' => $this->medidoresDisponibles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empleadoId = Auth::user()?->persona?->empleado?->id_empleado ?? Empleado::query()
            ->where('estado', 'activo')
            ->orderBy('id_empleado')
            ->value('id_empleado');

        $data = $request->validate([
            'fecha_lectura' => ['required', 'date', 'before_or_equal:today'],
            'lectura_anterior' => ['required', 'numeric', 'min:0'],
            'lectura_actual' => ['required', 'numeric', 'gte:lectura_anterior'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'id_medidor' => ['required', 'exists:medidores,id_medidor'],
        ]);

        $duplicada = Lectura::query()
            ->where('id_medidor', $data['id_medidor'])
            ->whereDate('fecha_lectura', $data['fecha_lectura'])
            ->exists();

        if ($duplicada) {
            return back()->withInput()->withErrors([
                'fecha_lectura' => 'Ya existe una lectura registrada para este medidor en la fecha seleccionada.',
            ]);
        }

        Lectura::create($data + [
            'id_empleado' => $empleadoId,
        ]);

        Cache::forget('facturas.billing_candidates');

        return redirect()
            ->route('tecnico.lecturas.index')
            ->with('success', 'Lecturacion registrada correctamente.');
    }

    private function medidoresDisponibles()
    {
        return Medidor::query()
            ->with(['socio.persona', 'lecturas' => fn ($query) => $query->orderByDesc('fecha_lectura')])
            ->where('estado', 'activo')
            ->orderBy('numero_serie')
            ->get()
            ->map(function (Medidor $medidor) {
                $ultimaLectura = $medidor->lecturas->first();

                return (object) [
                    'id_medidor' => $medidor->id_medidor,
                    'numero_serie' => $medidor->numero_serie,
                    'socio_nombre' => $medidor->socio?->persona?->nombre_completo ?? 'Sin socio',
                    'codigo_usuario' => $medidor->socio?->codigo_display ?? '-',
                    'lectura_sugerida' => (float) ($ultimaLectura?->lectura_actual ?? 0),
                    'ultima_fecha' => optional($ultimaLectura?->fecha_lectura)->format('d/m/Y'),
                ];
            });
    }
}
