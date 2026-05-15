<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Medidor;
use App\Models\Sector;
use App\Models\Socio;
use App\Models\Tarifa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function empleados(Request $request, string $format): Response
    {
        $query = Empleado::with(['persona', 'rol', 'user'])
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id_empleado');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->whereHas('persona', function ($persona) use ($term) {
                    $persona->where('nombres', 'ilike', "%{$term}%")
                        ->orWhere('apellidos', 'ilike', "%{$term}%")
                        ->orWhere('cedula_identidad', 'ilike', "%{$term}%")
                        ->orWhere('email', 'ilike', "%{$term}%");
                })->orWhereHas('rol', fn ($rol) => $rol->where('nombre', 'ilike', "%{$term}%"));
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('rol')) {
            $query->where('id_rol', $request->rol);
        }

        $rows = $query->get()->map(fn (Empleado $empleado) => [
            'Empleado' => $empleado->persona?->nombre_completo,
            'CI' => $empleado->persona?->cedula_identidad,
            'Telefono' => $empleado->persona?->telefono,
            'Correo' => $empleado->persona?->email,
            'Rol' => ucfirst($empleado->rol?->nombre ?? 'Sin rol'),
            'Usuario' => $empleado->user?->username,
            'Ingreso' => optional($empleado->fecha_ingreso)->format('d/m/Y'),
            'Estado' => ucfirst($empleado->estado),
        ]);

        return $this->download($format, 'empleados', 'Reporte de empleados', $rows);
    }

    public function socios(Request $request, string $format): Response
    {
        $hasOcultoColumn = Cache::remember('schema.socios.oculto', now()->addHours(12), fn () => Schema::hasColumn('socios', 'oculto'));

        $query = DB::table('socios as s')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('sectores as sec', 'sec.id_sector', '=', 's.id_sector')
            ->leftJoin('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
            ->leftJoin('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')->where('m.estado', '=', 'activo');
            })
            ->select([
                's.id_socio',
                's.numero_socio',
                's.direccion',
                's.estado',
                'p.cedula_identidad',
                'p.telefono',
                'sec.nombre as sector_nombre',
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

        $rows = $query->get()->map(fn ($socio) => [
            'Codigo' => $socio->codigo_display,
            'Socio' => $socio->nombre_completo,
            'CI' => $socio->cedula_identidad,
            'Telefono' => $socio->telefono,
            'Direccion' => $socio->direccion,
            'Sector' => $socio->sector_nombre,
            'Tarifa' => $socio->tarifa_nombre,
            'Medidor' => $socio->medidor_numero_serie,
            'Estado' => ucfirst($socio->estado),
            'Oculto' => $socio->oculto ? 'Si' : 'No',
        ]);

        return $this->download($format, 'socios', 'Reporte de socios', $rows);
    }

    public function tarifas(Request $request, string $format): Response
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

        $rows = $query->get()->map(function (Tarifa $tarifa) {
            $desglose = $tarifa->calcularDesglose(35);

            return [
                'Tarifa' => $tarifa->nombre,
                'Uso' => ucfirst($tarifa->tipo_uso ?? 'domestico'),
                'Cargo fijo agua' => number_format((float) $desglose['fixed_charge'], 2),
                'M3 incluidos' => number_format((float) $desglose['included_m3'], 2),
                'Excedente por m3' => number_format((float) $desglose['excess_rate'], 2),
                'Corte / reconexion' => number_format((float) $desglose['cutoff_penalty'], 2),
                'Estado' => ucfirst($tarifa->estado),
                'Socios' => $tarifa->socios_count,
                'Vigencia' => optional($tarifa->fecha_vigencia)->format('d/m/Y'),
            ];
        });

        return $this->download($format, 'tarifas', 'Reporte de tarifas', $rows);
    }

    public function gastos(Request $request, string $format): Response
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $rows = Gasto::query()
            ->with('empleado.persona')
            ->whereBetween('fecha_gasto', [$desde, $hasta])
            ->orderByDesc('fecha_gasto')
            ->orderByDesc('id_gasto')
            ->get()
            ->map(fn (Gasto $gasto) => [
                'Fecha' => optional($gasto->fecha_gasto)->format('d/m/Y'),
                'Concepto' => $gasto->concepto,
                'Categoria' => $gasto->categoria,
                'Monto' => number_format((float) $gasto->monto, 2),
                'Responsable' => $gasto->empleado?->persona?->nombre_completo,
                'Descripcion' => $gasto->descripcion,
            ]);

        return $this->download($format, 'gastos', 'Reporte de gastos', $rows);
    }

    public function medidores(Request $request, string $format): Response
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

        $rows = $query->get()->map(fn (Medidor $medidor) => [
            'Serie' => $medidor->numero_serie,
            'Marca' => $medidor->marca,
            'Modelo' => $medidor->modelo,
            'Socio' => $medidor->socio?->persona?->nombre_completo,
            'Codigo usuario' => $medidor->socio?->codigo_display,
            'Sector' => $medidor->socio?->sector?->nombre,
            'Instalador' => $medidor->empleadoInstalador?->persona?->nombre_completo,
            'Fecha instalacion' => optional($medidor->fecha_instalacion)->format('d/m/Y'),
            'Estado' => ucfirst($medidor->estado),
        ]);

        return $this->download($format, 'medidores', 'Reporte de medidores', $rows);
    }

    public function facturas(Request $request, string $format): Response
    {
        $query = DB::table('facturas as f')
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->select([
                'f.numero_factura',
                'f.fecha_emision',
                'f.consumo_m3',
                'f.total',
                'f.estado',
                'pf.nombre as periodo_nombre',
            ])
            ->selectRaw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as nombre_completo")
            ->selectRaw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo_display")
            ->orderByDesc('f.fecha_emision')
            ->orderByDesc('f.id_factura');

        if ($request->filled('buscar')) {
            $term = trim((string) $request->buscar);
            $query->where(function ($builder) use ($term) {
                $builder->where('f.numero_factura', 'ilike', "%{$term}%")
                    ->orWhere('f.estado', 'ilike', "%{$term}%")
                    ->orWhere('s.numero_socio', 'ilike', "%{$term}%")
                    ->orWhere('p.nombres', 'ilike', "%{$term}%")
                    ->orWhere('p.apellidos', 'ilike', "%{$term}%")
                    ->orWhere('p.cedula_identidad', 'ilike', "%{$term}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('f.estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('f.id_periodo', $request->periodo);
        }

        $rows = $query->get()->map(fn ($factura) => [
            'Factura' => $factura->numero_factura,
            'Codigo usuario' => $factura->codigo_display,
            'Socio' => $factura->nombre_completo,
            'Periodo' => $factura->periodo_nombre,
            'Emision' => optional($factura->fecha_emision)->format('d/m/Y'),
            'Consumo m3' => number_format((float) $factura->consumo_m3, 2),
            'Total' => number_format((float) $factura->total, 2),
            'Estado' => ucfirst($factura->estado),
        ]);

        return $this->download($format, 'facturas', 'Reporte de facturas', $rows);
    }

    private function download(string $format, string $filename, string $title, Collection $rows): Response
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        if ($format === 'pdf') {
            return Pdf::loadView('exports.table-pdf', [
                'title' => $title,
                'rows' => $rows,
            ])->download($filename . '.pdf');
        }

        $html = view('exports.table-excel', [
            'title' => $title,
            'rows' => $rows,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
        ]);
    }
}
