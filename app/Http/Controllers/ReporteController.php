<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Factura;
use App\Models\Gasto;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use App\Models\Socio;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $periodoId = $request->input('periodo');

        $cobranza = Cobro::query()
            ->with(['factura.socio.persona', 'metodoPago', 'empleado.persona'])
            ->whereBetween('fecha_cobro', [$desde, $hasta])
            ->where('estado', '!=', 'anulado')
            ->orderByDesc('fecha_cobro')
            ->get();

        $facturasQuery = Factura::query()
            ->with(['socio.persona', 'periodo'])
            ->where('estado', '!=', 'anulada');

        if ($periodoId) {
            $facturasQuery->where('id_periodo', $periodoId);
        }

        $facturas = $facturasQuery->orderByDesc('fecha_emision')->get();

        $consumos = $facturas
            ->groupBy('id_socio')
            ->map(function ($grupo) {
                $factura = $grupo->first();

                return [
                    'socio' => $factura->socio?->persona?->nombre_completo ?? 'Sin socio',
                    'codigo' => $factura->socio?->codigo_display ?? '-',
                    'consumo_total' => $grupo->sum(fn($item) => (float) $item->consumo_m3),
                    'monto_total' => $grupo->sum(fn($item) => (float) $item->total),
                    'facturas' => $grupo->count(),
                ];
            })
            ->sortByDesc('consumo_total')
            ->values();

        $morosos = Factura::query()
            ->with(['socio.persona', 'periodo'])
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderByDesc('fecha_emision')
            ->get()
            ->groupBy('id_socio')
            ->map(function ($grupo) {
                $factura = $grupo->first();
                $saldo = $grupo->sum(function ($item) {
                    $pagado = $item->cobros()->where('estado', '!=', 'anulado')->sum('monto_pagado');

                    return max(0, (float) $item->total - (float) $pagado);
                });

                return [
                    'socio' => $factura->socio?->persona?->nombre_completo ?? 'Sin socio',
                    'codigo' => $factura->socio?->codigo_display ?? '-',
                    'facturas_pendientes' => $grupo->count(),
                    'saldo' => $saldo,
                    'ultima_factura' => optional($factura->fecha_emision)?->format('d/m/Y'),
                ];
            })
            ->sortByDesc('saldo')
            ->values();

        $gastos = Gasto::query()
            ->with('empleado.persona')
            ->whereBetween('fecha_gasto', [$desde, $hasta])
            ->orderByDesc('fecha_gasto')
            ->get();

        $ingresosPorDia = $cobranza
            ->groupBy(fn ($item) => optional($item->fecha_cobro)->format('Y-m-d'))
            ->map(fn ($items, $fecha) => [
                'fecha' => $fecha,
                'total' => $items->sum('monto_pagado'),
            ])
            ->values();

        $egresosPorDia = $gastos
            ->groupBy(fn ($item) => optional($item->fecha_gasto)->format('Y-m-d'))
            ->map(fn ($items, $fecha) => [
                'fecha' => $fecha,
                'total' => $items->sum('monto'),
            ])
            ->values();

        $gastosPorCategoria = $gastos
            ->groupBy('categoria')
            ->map(fn ($items, $categoria) => [
                'categoria' => $categoria ?: 'Sin categoria',
                'total' => round($items->sum('monto'), 2),
            ])
            ->values();

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $nuevosSociosMes = Socio::whereBetween('created_at', [$inicioMes, $finMes])->count();
        $lecturasMes = Lectura::whereBetween('fecha_lectura', [$inicioMes, $finMes])->count();
        $multasMes = $facturas
            ->sum(function ($factura) {
                $consumo = (float) $factura->consumo_m3;

                return $consumo > 30 ? 30 : 0;
            });

        $actividadMensual = collect(range(1, 12))
            ->map(function ($month) {
                $start = now()->copy()->startOfYear()->month($month)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                return [
                    'mes' => $start->translatedFormat('M'),
                    'ingresos' => (float) Cobro::whereBetween('fecha_cobro', [$start->toDateString(), $end->toDateString()])
                        ->where('estado', '!=', 'anulado')
                        ->sum('monto_pagado'),
                    'egresos' => (float) Gasto::whereBetween('fecha_gasto', [$start->toDateString(), $end->toDateString()])
                        ->sum('monto'),
                    'usuarios' => (int) Socio::whereBetween('created_at', [$start->toDateString(), $end->toDateString()])->count(),
                    'lecturas' => (int) Lectura::whereBetween('fecha_lectura', [$start->toDateString(), $end->toDateString()])->count(),
                ];
            });

        return view('reportes.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'periodoId' => $periodoId,
            'periodos' => PeriodoFacturacion::orderByDesc('fecha_inicio')->get(),
            'cobranza' => $cobranza,
            'consumos' => $consumos,
            'morosos' => $morosos,
            'gastos' => $gastos,
            'ingresosPorDia' => $ingresosPorDia,
            'egresosPorDia' => $egresosPorDia,
            'gastosPorCategoria' => $gastosPorCategoria,
            'actividadMensual' => $actividadMensual,
            'resumen' => [
                'recaudado' => $cobranza->sum('monto_pagado'),
                'cobros' => $cobranza->count(),
                'consumo_m3' => $consumos->sum('consumo_total'),
                'saldo_moroso' => $morosos->sum('saldo'),
                'egresos' => $gastos->sum('monto'),
                'nuevos_socios_mes' => $nuevosSociosMes,
                'multas_mes' => $multasMes,
                'lecturas_mes' => $lecturasMes,
            ],
        ]);
    }
}
