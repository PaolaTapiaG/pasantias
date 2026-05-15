<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GastoController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $gastos = Gasto::query()
            ->with('empleado.persona')
            ->whereBetween('fecha_gasto', [$desde, $hasta])
            ->orderByDesc('fecha_gasto')
            ->orderByDesc('id_gasto')
            ->paginate(12)
            ->withQueryString();

        return view('gastos.index', [
            'gastos' => $gastos,
            'desde' => $desde,
            'hasta' => $hasta,
            'totalGastos' => Gasto::whereBetween('fecha_gasto', [$desde, $hasta])->sum('monto'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empleadoId = Auth::user()?->persona?->empleado?->id_empleado;

        $data = $request->validate([
            'fecha_gasto' => ['required', 'date'],
            'concepto' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        Gasto::create($data + ['id_empleado' => $empleadoId]);

        return redirect()
            ->route('admin.gastos.index')
            ->with('success', 'Gasto registrado correctamente.');
    }
}
