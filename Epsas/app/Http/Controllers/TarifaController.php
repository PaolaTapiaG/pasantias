<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TarifaController extends Controller
{
    public function index(Request $request)
    {
        return view('tarifas.index', [
            'tarifas' => $this->tariffPaginator($request),
        ]);
    }

    public function warmIndexCache(): void
    {
        $this->tariffPaginator(Request::create('/admin/tarifas', 'GET'), url('/admin/tarifas'));
    }

    private function tariffPaginator(Request $request, ?string $path = null)
    {
        $cacheKey = 'tarifas.index.'.md5(json_encode($request->query()));

        return OperationalCache::rememberDomain('billing', $cacheKey, fn () => $this->tariffIndexQuery($request)
            ->simplePaginate(12)
            ->withPath($path ?? url('/admin/tarifas'))
            ->appends($request->query())
            ->through(function ($tarifa) {
                $tarifa->fecha_vigencia = $tarifa->fecha_vigencia ? Carbon::parse($tarifa->fecha_vigencia) : null;
                $tarifa->socios_count = (int) ($tarifa->socios_count ?? 0);

                return $tarifa;
            }));
    }

    private function tariffIndexQuery(Request $request)
    {
        $query = DB::table('tarifas as t')
            ->select([
                't.id_tarifa',
                't.nombre',
                't.tipo_uso',
                't.precio_m3_base',
                't.consumo_minimo_m3',
                't.cargo_fijo',
                't.fecha_vigencia',
                't.estado',
            ])
            ->selectRaw('(select COUNT(*) from socios as s where s.id_tarifa = t.id_tarifa) as socios_count')
            ->orderByDesc('t.fecha_vigencia')
            ->orderBy('t.nombre');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('t.nombre', 'ilike', "%{$term}%")
                    ->orWhere('t.estado', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('t.estado', $request->estado);
        }

        return $query;
    }

    public function create()
    {
        return view('tarifas.create');
    }

    public function store(Request $request)
    {
        $tarifa = Tarifa::create($this->validateTarifa($request));
        $this->flushTariffCaches();

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
        $this->flushTariffCaches();

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

    private function flushTariffCaches(): void
    {
        Cache::add('tarifas:index:version', 1, now()->addYears(2));
        Cache::increment('tarifas:index:version');
        OperationalCache::bumpDomain('billing');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('socios.form.tarifas');
        OperationalCache::forget('billing:signals');
    }
}
