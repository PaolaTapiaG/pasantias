<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GastoController extends Controller
{
    private const EXPENSE_CATEGORIES = [
        'Gasto de oficina',
        'Materiales y cañerías',
        'Pago de salarios',
        'Bonos laborales',
        'Aguinaldos',
        'Mantenimiento',
        'Combustible',
        'Servicios basicos',
        'Otros',
    ];

    public function index(Request $request): View
    {
        $data = $this->expenseIndexData($request);

        return view('gastos.index', $data);
    }

    public function warmIndexCache(): void
    {
        $this->expenseIndexData(Request::create('/admin/gastos', 'GET'), url('/admin/gastos'));
    }

    private function expenseIndexData(Request $request, ?string $path = null): array
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $categoria = $request->input('categoria');

        $query = DB::table('gastos as g')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'g.id_empleado')
            ->leftJoin('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->select(['g.id_gasto', 'g.fecha_gasto', 'g.concepto', 'g.categoria', 'g.descripcion', 'g.monto', 'g.id_empleado'])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as empleado_nombre")
            ->whereBetween('g.fecha_gasto', [$desde, $hasta])
            ->when($categoria, fn ($builder) => $builder->where('g.categoria', $categoria))
            ->orderByDesc('g.fecha_gasto')
            ->orderByDesc('g.id_gasto');

        $suffix = md5(json_encode($request->query()));

        return [
            'gastos' => OperationalCache::rememberDomain('billing', 'gastos.index.'.$suffix, fn () => $query
                ->simplePaginate(12)
                ->withPath($path ?? url('/admin/gastos'))
                ->appends($request->query())
                ->through(fn ($row) => $this->expenseRowForView($row))),
            'desde' => $desde,
            'hasta' => $hasta,
            'categoria' => $categoria,
            'categoriasGasto' => self::EXPENSE_CATEGORIES,
            'totalGastos' => OperationalCache::rememberDomain('billing', 'gastos.total.'.md5(json_encode([$desde, $hasta, $categoria])), fn () => (float) Gasto::query()
                ->whereBetween('fecha_gasto', [$desde, $hasta])
                ->when($categoria, fn ($builder) => $builder->where('categoria', $categoria))
                ->sum('monto')),
            'categoryTotals' => OperationalCache::rememberDomain('billing', 'gastos.categories.'.md5(json_encode([$desde, $hasta])), fn () => DB::table('gastos')
                ->whereBetween('fecha_gasto', [$desde, $hasta])
                ->selectRaw('categoria, ROUND(COALESCE(SUM(monto), 0), 2) as total')
                ->groupBy('categoria')
                ->pluck('total', 'categoria')),
        ];
    }

    private function expenseRowForView(object $row): object
    {
        $row->fecha_gasto = $row->fecha_gasto ? Carbon::parse($row->fecha_gasto) : null;
        $row->empleado = $row->id_empleado ? (object) [
            'id_empleado' => $row->id_empleado,
            'persona' => (object) [
                'nombre_completo' => $row->empleado_nombre ?: 'Sin responsable',
            ],
        ] : null;

        return $row;
    }

    public function store(Request $request): RedirectResponse
    {
        $empleadoId = Auth::user()?->persona?->empleado?->id_empleado;

        $data = $request->validate([
            'fecha_gasto' => ['required', 'date'],
            'concepto' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'string', 'max:80', Rule::in(self::EXPENSE_CATEGORIES)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        Gasto::create($data + ['id_empleado' => $empleadoId]);
        OperationalCache::bumpDomain('billing');

        return redirect()
            ->route('admin.gastos.index')
            ->with('success', 'Gasto registrado correctamente.');
    }
}
