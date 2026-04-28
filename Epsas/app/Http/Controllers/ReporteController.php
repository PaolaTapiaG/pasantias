<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Factura;
use App\Models\PeriodoFacturacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('reportes.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'periodoId' => $periodoId,
            'periodos' => PeriodoFacturacion::orderByDesc('fecha_inicio')->get(),
            'cobranza' => $cobranza,
            'consumos' => $consumos,
            'morosos' => $morosos,
            'resumen' => [
                'recaudado' => $cobranza->sum('monto_pagado'),
                'cobros' => $cobranza->count(),
                'consumo_m3' => $consumos->sum('consumo_total'),
                'saldo_moroso' => $morosos->sum('saldo'),
            ],
        ]);
    }
}
