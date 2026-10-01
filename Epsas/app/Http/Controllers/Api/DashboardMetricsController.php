<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Factura;
use App\Models\IngresoAdministrativo;
use App\Models\Socio;
use App\Support\OperationalCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetricsController extends Controller
{
    public function secretaria(): JsonResponse
    {
        $metrics = Cache::remember('api.dashboard.secretaria', now()->addMinutes(5), function () {
            $inicioMes = now()->startOfMonth()->toDateString();
            $finMes = now()->endOfMonth()->toDateString();

            $ingresosFacturas = Cobro::whereBetween('fecha_cobro', [$inicioMes, $finMes])
                ->where('estado', '!=', 'anulado')
                ->sum('monto_pagado');
            $ingresosAdministrativos = IngresoAdministrativo::whereBetween('fecha_ingreso', [$inicioMes, $finMes])
                ->sum('monto');

            return [
                'socios' => Socio::count(),
                'facturas_pendientes' => Factura::whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count(),
                'cobros_pendientes' => Factura::whereIn('estado', ['pendiente', 'parcial', 'vencida'])->count(),
                'ingresos_mensuales' => round((float) ($ingresosFacturas + $ingresosAdministrativos), 2),
            ];
        });

        return response()->json($metrics);
    }

    public function tecnico(): JsonResponse
    {
        $metrics = OperationalCache::remember('api-dashboard-tecnico', function () {
            $summary = DB::query()
                ->selectRaw("
                    (SELECT COUNT(*) FROM medidores) as medidores_registrados,
                    (SELECT COUNT(*) FROM medidores WHERE estado = 'activo') as medidores_activos,
                    (SELECT COUNT(*) FROM lecturas) as lecturas_cargadas,
                    (SELECT COUNT(*) FROM medidores WHERE estado IN ('inactivo', 'danado')) as pendientes_tecnicos
                ")
                ->first();

            return [
                'medidores_registrados' => (int) ($summary?->medidores_registrados ?? 0),
                'medidores_activos' => (int) ($summary?->medidores_activos ?? 0),
                'lecturas_cargadas' => (int) ($summary?->lecturas_cargadas ?? 0),
                'pendientes_tecnicos' => (int) ($summary?->pendientes_tecnicos ?? 0),
            ];
        });

        return response()->json($metrics);
    }
}
