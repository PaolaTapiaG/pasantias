<?php

namespace App\Services;

use App\Models\PeriodoFacturacion;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function index(Request $request)
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $periodoId = $request->filled('periodo') ? (int) $request->input('periodo') : null;
        $report = $this->reportData($desde, $hasta, $periodoId);

        return view('reportes.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'periodoId' => $periodoId,
            'periodos' => $this->periodOptions(),
            ...$report,
        ]);
    }

    public function warmIndexCache(): void
    {
        $this->reportData(now()->startOfMonth()->toDateString(), now()->toDateString(), null);
        $this->periodOptions();
    }

    private function reportData(string $desde, string $hasta, ?int $periodoId): array
    {
        $cacheKey = md5(json_encode([$desde, $hasta, $periodoId]));

        return OperationalCache::rememberDomain('billing', 'reportes.'.$cacheKey, function () use ($desde, $hasta, $periodoId) {
            $payload = $this->reportPayload($desde, $hasta, $periodoId);
            $totals = (object) ($this->decodeJson($payload->totals_json)->first() ?? []);
            $monthStats = (object) ($this->decodeJson($payload->month_stats_json)->first() ?? []);

            $financialRows = $this->decodeJson($payload->financial_rows)->map(fn ($row) => [
                'fecha' => $row['fecha'],
                'ingresos' => (float) $row['ingresos'],
                'egresos' => (float) $row['egresos'],
                'cobros_count' => (int) $row['cobros_count'],
            ]);
            $cobranza = $this->decodeJson($payload->latest_payments)->map(function ($row) {
                $payment = (object) $row;
                $payment->fecha_cobro = $payment->fecha_cobro ? Carbon::parse($payment->fecha_cobro) : null;
                $payment->factura = (object) [
                    'socio' => (object) [
                        'persona' => (object) [
                            'nombre_completo' => $payment->socio_nombre ?: 'Sin socio',
                        ],
                    ],
                ];
                $payment->metodoPago = (object) [
                    'nombre' => $payment->metodo_nombre ?: 'Sin metodo',
                ];

                return $payment;
            });
            $consumos = $this->decodeJson($payload->consumption_rows)->map(fn ($row) => [
                'socio' => $row['socio'] ?: 'Sin socio',
                'codigo' => $row['codigo'] ?: '-',
                'consumo_total' => (float) $row['consumo_total'],
                'monto_total' => (float) $row['monto_total'],
                'facturas' => (int) $row['facturas'],
            ]);
            $morosos = $this->decodeJson($payload->delinquency_rows)->map(fn ($row) => [
                'socio' => $row['socio'] ?: 'Sin socio',
                'codigo' => $row['codigo'] ?: '-',
                'facturas_pendientes' => (int) $row['facturas_pendientes'],
                'saldo' => (float) $row['saldo'],
                'ultima_factura' => ! empty($row['ultima_factura']) ? Carbon::parse($row['ultima_factura'])->format('d/m/Y') : null,
            ]);
            $gastos = $this->decodeJson($payload->latest_expenses)->map(function ($row) {
                $expense = (object) $row;
                $expense->fecha_gasto = $expense->fecha_gasto ? Carbon::parse($expense->fecha_gasto) : null;

                return $expense;
            });
            $gastosPorCategoria = $this->decodeJson($payload->expense_categories)->map(fn ($row) => [
                'categoria' => $row['categoria'],
                'total' => (float) $row['total'],
            ]);
            $actividadMensual = $this->decodeJson($payload->monthly_rows)->map(function ($row) {
                $monthDate = now()->copy()->month((int) $row['month'])->startOfMonth();

                return [
                    'mes' => ucfirst($monthDate->translatedFormat('M')),
                    'ingresos' => round((float) $row['ingresos'], 2),
                    'egresos' => round((float) $row['egresos'], 2),
                    'usuarios' => (int) $row['usuarios'],
                    'lecturas' => (int) $row['lecturas'],
                ];
            });

            $financeMax = max(
                1,
                (float) $financialRows->max('ingresos'),
                (float) $financialRows->max('egresos')
            );
            $expenseTotal = max(0, (float) $gastosPorCategoria->sum('total'));
            $expenseSegments = $this->expenseSegments($gastosPorCategoria, $expenseTotal);

            return [
                'cobranza' => $cobranza,
                'consumos' => $consumos,
                'morosos' => $morosos,
                'gastos' => $gastos,
                'ingresosPorDia' => $financialRows->map(fn ($row) => ['fecha' => $row['fecha'], 'total' => $row['ingresos']])->values(),
                'egresosPorDia' => $financialRows->map(fn ($row) => ['fecha' => $row['fecha'], 'total' => $row['egresos']])->values(),
                'gastosPorCategoria' => $gastosPorCategoria,
                'actividadMensual' => $actividadMensual,
                'financeBars' => $financialRows->map(fn ($row) => [
                    'label' => Carbon::parse($row['fecha'])->format('d/m'),
                    'ingresos' => (float) $row['ingresos'],
                    'egresos' => (float) $row['egresos'],
                    'ingresos_pct' => round(((float) $row['ingresos'] / $financeMax) * 100, 2),
                    'egresos_pct' => round(((float) $row['egresos'] / $financeMax) * 100, 2),
                ])->values(),
                'financeScale' => collect([1, 0.75, 0.5, 0.25, 0])->map(fn ($step) => round($financeMax * $step, 2))->values(),
                'expenseSegments' => $expenseSegments,
                'expensePieGradient' => $this->pieGradient($expenseSegments),
                'monthlyMax' => max(1, (int) $actividadMensual->max('usuarios'), (int) $actividadMensual->max('lecturas')),
                'monthlyMoneyMax' => max(1, (float) $actividadMensual->max('ingresos'), (float) $actividadMensual->max('egresos')),
                'resumen' => [
                    'recaudado' => round((float) $financialRows->sum('ingresos'), 2),
                    'cobros' => (int) $financialRows->sum('cobros_count'),
                    'consumo_m3' => round((float) ($totals->consumo_m3 ?? 0), 2),
                    'saldo_moroso' => round((float) ($totals->saldo_moroso ?? 0), 2),
                    'egresos' => round((float) $financialRows->sum('egresos'), 2),
                    'nuevos_socios_mes' => (int) ($monthStats->nuevos_socios_mes ?? 0),
                    'multas_mes' => (float) ($monthStats->multas_mes ?? 0),
                    'lecturas_mes' => (int) ($monthStats->lecturas_mes ?? 0),
                ],
            ];
        });
    }

    private function reportPayload(string $desde, string $hasta, ?int $periodoId): object
    {
        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();
        $yearStartTimestamp = now()->startOfYear()->toDateTimeString();
        $yearEndTimestamp = now()->endOfYear()->toDateTimeString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthStartTimestamp = now()->startOfMonth()->toDateTimeString();
        $monthEndTimestamp = now()->endOfMonth()->toDateTimeString();

        return DB::selectOne(
            "
            WITH payments_by_invoice AS (
                SELECT id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado
                FROM cobros
                GROUP BY id_factura
            ),
            financial AS (
                SELECT fecha,
                       ROUND(SUM(ingresos), 2) as ingresos,
                       ROUND(SUM(egresos), 2) as egresos,
                       SUM(cobros_count)::int as cobros_count
                FROM (
                    SELECT fecha_cobro::date as fecha, SUM(monto_pagado) as ingresos, 0::numeric as egresos, COUNT(*) as cobros_count
                    FROM cobros
                    WHERE fecha_cobro BETWEEN ? AND ? AND estado <> 'anulado'
                    GROUP BY fecha_cobro::date
                    UNION ALL
                    SELECT fecha_ingreso::date as fecha, SUM(monto) as ingresos, 0::numeric as egresos, COUNT(*) as cobros_count
                    FROM ingresos_administrativos
                    WHERE fecha_ingreso BETWEEN ? AND ?
                    GROUP BY fecha_ingreso::date
                    UNION ALL
                    SELECT fecha_gasto::date as fecha, 0::numeric as ingresos, SUM(monto) as egresos, 0 as cobros_count
                    FROM gastos
                    WHERE fecha_gasto BETWEEN ? AND ?
                    GROUP BY fecha_gasto::date
                ) flow
                GROUP BY fecha
                ORDER BY fecha
            ),
            latest_payments AS (
                SELECT id_cobro,
                       fecha_cobro,
                       monto_pagado,
                       metodo_nombre,
                       socio_nombre
                FROM (
                    SELECT c.id_cobro::text as id_cobro,
                           c.fecha_cobro::date as fecha_cobro,
                           c.monto_pagado,
                           mp.nombre as metodo_nombre,
                           TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre,
                           c.id_cobro as sort_id
                    FROM cobros c
                    LEFT JOIN facturas f ON f.id_factura = c.id_factura
                    LEFT JOIN socios s ON s.id_socio = f.id_socio
                    LEFT JOIN personas p ON p.id_persona = s.id_persona
                    LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
                    WHERE c.fecha_cobro BETWEEN ? AND ? AND c.estado <> 'anulado'
                    UNION ALL
                    SELECT ia.id_ingreso::text as id_cobro,
                           ia.fecha_ingreso::date as fecha_cobro,
                           ia.monto as monto_pagado,
                           'Ingreso administrativo' as metodo_nombre,
                           TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre,
                           ia.id_ingreso as sort_id
                    FROM ingresos_administrativos ia
                    LEFT JOIN socios s ON s.id_socio = ia.id_socio
                    LEFT JOIN personas p ON p.id_persona = s.id_persona
                    WHERE ia.fecha_ingreso BETWEEN ? AND ?
                ) payment_flow
                ORDER BY fecha_cobro DESC, sort_id DESC
                LIMIT 8
            ),
            consumption_rows AS (
                SELECT TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio,
                       COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo,
                       ROUND(COALESCE(SUM(f.consumo_m3), 0), 2) as consumo_total,
                       ROUND(COALESCE(SUM(f.total), 0), 2) as monto_total,
                       COUNT(*) as facturas
                FROM facturas f
                LEFT JOIN socios s ON s.id_socio = f.id_socio
                LEFT JOIN personas p ON p.id_persona = s.id_persona
                WHERE f.estado <> 'anulada' AND (?::int IS NULL OR f.id_periodo = ?)
                GROUP BY s.id_socio, s.numero_socio, p.nombres, p.apellidos
                ORDER BY consumo_total DESC
                LIMIT 12
            ),
            delinquency_rows AS (
                SELECT TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio,
                       COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo,
                       COUNT(*) as facturas_pendientes,
                       ROUND(SUM(GREATEST(f.total - COALESCE(pbi.pagado, 0), 0)), 2) as saldo,
                       MAX(f.fecha_emision) as ultima_factura
                FROM facturas f
                LEFT JOIN payments_by_invoice pbi ON pbi.id_factura = f.id_factura
                LEFT JOIN socios s ON s.id_socio = f.id_socio
                LEFT JOIN personas p ON p.id_persona = s.id_persona
                WHERE f.estado IN ('pendiente', 'parcial', 'vencida') AND (?::int IS NULL OR f.id_periodo = ?)
                GROUP BY s.id_socio, s.numero_socio, p.nombres, p.apellidos
                HAVING SUM(GREATEST(f.total - COALESCE(pbi.pagado, 0), 0)) > 0
                ORDER BY saldo DESC
                LIMIT 12
            ),
            latest_expenses AS (
                SELECT g.id_gasto,
                       g.fecha_gasto,
                       g.concepto,
                       g.categoria,
                       g.monto,
                       TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as empleado_nombre
                FROM gastos g
                LEFT JOIN empleados e ON e.id_empleado = g.id_empleado
                LEFT JOIN personas p ON p.id_persona = e.id_persona
                WHERE g.fecha_gasto BETWEEN ? AND ?
                ORDER BY g.fecha_gasto DESC, g.id_gasto DESC
                LIMIT 8
            ),
            expense_categories AS (
                SELECT COALESCE(NULLIF(categoria, ''), 'Sin categoria') as categoria,
                       ROUND(SUM(monto), 2) as total
                FROM gastos
                WHERE fecha_gasto BETWEEN ? AND ?
                GROUP BY COALESCE(NULLIF(categoria, ''), 'Sin categoria')
                ORDER BY total DESC
            ),
            monthly_rows AS (
                WITH months AS (SELECT generate_series(1, 12) as month)
                SELECT months.month,
                       COALESCE(c.total, 0) + COALESCE(ia.total, 0) as ingresos,
                       COALESCE(g.total, 0) as egresos,
                       COALESCE(s.total, 0) as usuarios,
                       COALESCE(l.total, 0) as lecturas
                FROM months
                LEFT JOIN (
                    SELECT EXTRACT(MONTH FROM fecha_cobro)::int as month, SUM(monto_pagado) as total
                    FROM cobros
                    WHERE fecha_cobro BETWEEN ? AND ? AND estado <> 'anulado'
                    GROUP BY EXTRACT(MONTH FROM fecha_cobro)::int
                ) c ON c.month = months.month
                LEFT JOIN (
                    SELECT EXTRACT(MONTH FROM fecha_ingreso)::int as month, SUM(monto) as total
                    FROM ingresos_administrativos
                    WHERE fecha_ingreso BETWEEN ? AND ?
                    GROUP BY EXTRACT(MONTH FROM fecha_ingreso)::int
                ) ia ON ia.month = months.month
                LEFT JOIN (
                    SELECT EXTRACT(MONTH FROM fecha_gasto)::int as month, SUM(monto) as total
                    FROM gastos
                    WHERE fecha_gasto BETWEEN ? AND ?
                    GROUP BY EXTRACT(MONTH FROM fecha_gasto)::int
                ) g ON g.month = months.month
                LEFT JOIN (
                    SELECT EXTRACT(MONTH FROM created_at)::int as month, COUNT(*) as total
                    FROM socios
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY EXTRACT(MONTH FROM created_at)::int
                ) s ON s.month = months.month
                LEFT JOIN (
                    SELECT EXTRACT(MONTH FROM fecha_lectura)::int as month, COUNT(*) as total
                    FROM lecturas
                    WHERE fecha_lectura BETWEEN ? AND ?
                    GROUP BY EXTRACT(MONTH FROM fecha_lectura)::int
                ) l ON l.month = months.month
                ORDER BY months.month
            ),
            totals AS (
                SELECT
                    (SELECT ROUND(COALESCE(SUM(consumo_m3), 0), 2) FROM facturas f WHERE f.estado <> 'anulada' AND (?::int IS NULL OR f.id_periodo = ?)) as consumo_m3,
                    (SELECT ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(pbi.pagado, 0), 0)), 0), 2)
                     FROM facturas f
                     LEFT JOIN payments_by_invoice pbi ON pbi.id_factura = f.id_factura
                     WHERE f.estado IN ('pendiente', 'parcial', 'vencida') AND (?::int IS NULL OR f.id_periodo = ?)) as saldo_moroso
            ),
            month_stats AS (
                SELECT
                    (SELECT COUNT(*) FROM socios WHERE created_at BETWEEN ? AND ?) as nuevos_socios_mes,
                    (SELECT COUNT(*) FROM lecturas WHERE fecha_lectura BETWEEN ? AND ?) as lecturas_mes,
                    (SELECT COUNT(*) * 30 FROM facturas WHERE fecha_emision BETWEEN ? AND ? AND consumo_m3 > 30 AND (?::int IS NULL OR id_periodo = ?)) as multas_mes
            )
            SELECT
                (SELECT COALESCE(json_agg(row_to_json(financial)), '[]'::json) FROM financial) as financial_rows,
                (SELECT COALESCE(json_agg(row_to_json(latest_payments)), '[]'::json) FROM latest_payments) as latest_payments,
                (SELECT COALESCE(json_agg(row_to_json(consumption_rows)), '[]'::json) FROM consumption_rows) as consumption_rows,
                (SELECT COALESCE(json_agg(row_to_json(delinquency_rows)), '[]'::json) FROM delinquency_rows) as delinquency_rows,
                (SELECT COALESCE(json_agg(row_to_json(latest_expenses)), '[]'::json) FROM latest_expenses) as latest_expenses,
                (SELECT COALESCE(json_agg(row_to_json(expense_categories)), '[]'::json) FROM expense_categories) as expense_categories,
                (SELECT COALESCE(json_agg(row_to_json(monthly_rows)), '[]'::json) FROM monthly_rows) as monthly_rows,
                (SELECT json_agg(row_to_json(totals)) FROM totals) as totals_json,
                (SELECT json_agg(row_to_json(month_stats)) FROM month_stats) as month_stats_json
            ",
            [
                $desde, $hasta, $desde, $hasta, $desde, $hasta,
                $desde, $hasta, $desde, $hasta,
                $periodoId, $periodoId,
                $periodoId, $periodoId,
                $desde, $hasta,
                $desde, $hasta,
                $yearStart, $yearEnd,
                $yearStart, $yearEnd,
                $yearStart, $yearEnd,
                $yearStartTimestamp, $yearEndTimestamp,
                $yearStart, $yearEnd,
                $periodoId, $periodoId,
                $periodoId, $periodoId,
                $monthStartTimestamp, $monthEndTimestamp,
                $monthStart, $monthEnd,
                $monthStart, $monthEnd,
                $periodoId, $periodoId,
            ]
        );
    }

    private function decodeJson(mixed $value)
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return collect(is_array($decoded) ? $decoded : []);
    }

    private function latestPayments(string $desde, string $hasta)
    {
        return collect(DB::select(
            "
            SELECT id_cobro, fecha_cobro, monto_pagado, metodo_nombre, socio_nombre
            FROM (
                SELECT c.id_cobro::text as id_cobro,
                       c.fecha_cobro::date as fecha_cobro,
                       c.monto_pagado,
                       mp.nombre as metodo_nombre,
                       TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre,
                       c.id_cobro as sort_id
                FROM cobros c
                LEFT JOIN facturas f ON f.id_factura = c.id_factura
                LEFT JOIN socios s ON s.id_socio = f.id_socio
                LEFT JOIN personas p ON p.id_persona = s.id_persona
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
                WHERE c.fecha_cobro BETWEEN ? AND ? AND c.estado <> 'anulado'
                UNION ALL
                SELECT ia.id_ingreso::text as id_cobro,
                       ia.fecha_ingreso::date as fecha_cobro,
                       ia.monto as monto_pagado,
                       'Ingreso administrativo' as metodo_nombre,
                       TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio_nombre,
                       ia.id_ingreso as sort_id
                FROM ingresos_administrativos ia
                LEFT JOIN socios s ON s.id_socio = ia.id_socio
                LEFT JOIN personas p ON p.id_persona = s.id_persona
                WHERE ia.fecha_ingreso BETWEEN ? AND ?
            ) payment_flow
            ORDER BY fecha_cobro DESC, sort_id DESC
            LIMIT 8
            ",
            [$desde, $hasta, $desde, $hasta]
        ))->map(function ($row) {
            $row->fecha_cobro = $row->fecha_cobro ? Carbon::parse($row->fecha_cobro) : null;
            $row->factura = (object) [
                'socio' => (object) [
                    'persona' => (object) [
                        'nombre_completo' => $row->socio_nombre ?: 'Sin socio',
                    ],
                ],
            ];
            $row->metodoPago = (object) [
                'nombre' => $row->metodo_nombre ?: 'Sin metodo',
            ];

            return $row;
        });
    }

    private function consumptionRanking(?int $periodoId)
    {
        return DB::table('facturas as f')
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->where('f.estado', '!=', 'anulada')
            ->when($periodoId, fn ($query) => $query->where('f.id_periodo', $periodoId))
            ->groupBy('s.id_socio', 's.numero_socio', 'p.nombres', 'p.apellidos')
            ->orderByDesc('consumo_total')
            ->limit(12)
            ->get([
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio"),
                DB::raw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo"),
                DB::raw('ROUND(COALESCE(SUM(f.consumo_m3), 0), 2) as consumo_total'),
                DB::raw('ROUND(COALESCE(SUM(f.total), 0), 2) as monto_total'),
                DB::raw('COUNT(*) as facturas'),
            ])
            ->map(fn ($row) => [
                'socio' => $row->socio ?: 'Sin socio',
                'codigo' => $row->codigo ?: '-',
                'consumo_total' => (float) $row->consumo_total,
                'monto_total' => (float) $row->monto_total,
                'facturas' => (int) $row->facturas,
            ])
            ->values();
    }

    private function delinquencyRanking(?int $periodoId)
    {
        $paymentsByInvoice = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return DB::table('facturas as f')
            ->leftJoinSub($paymentsByInvoice, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->leftJoin('socios as s', 's.id_socio', '=', 'f.id_socio')
            ->leftJoin('personas as p', 'p.id_persona', '=', 's.id_persona')
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->when($periodoId, fn ($query) => $query->where('f.id_periodo', $periodoId))
            ->groupBy('s.id_socio', 's.numero_socio', 'p.nombres', 'p.apellidos')
            ->havingRaw('SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)) > 0')
            ->orderByDesc('saldo')
            ->limit(12)
            ->get([
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as socio"),
                DB::raw("COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) as codigo"),
                DB::raw('COUNT(*) as facturas_pendientes'),
                DB::raw('ROUND(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 2) as saldo'),
                DB::raw('MAX(f.fecha_emision) as ultima_factura'),
            ])
            ->map(fn ($row) => [
                'socio' => $row->socio ?: 'Sin socio',
                'codigo' => $row->codigo ?: '-',
                'facturas_pendientes' => (int) $row->facturas_pendientes,
                'saldo' => (float) $row->saldo,
                'ultima_factura' => $row->ultima_factura ? Carbon::parse($row->ultima_factura)->format('d/m/Y') : null,
            ])
            ->values();
    }

    private function latestExpenses(string $desde, string $hasta)
    {
        return DB::table('gastos as g')
            ->leftJoin('empleados as e', 'e.id_empleado', '=', 'g.id_empleado')
            ->leftJoin('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->whereBetween('g.fecha_gasto', [$desde, $hasta])
            ->orderByDesc('g.fecha_gasto')
            ->orderByDesc('g.id_gasto')
            ->limit(8)
            ->get([
                'g.id_gasto',
                'g.fecha_gasto',
                'g.concepto',
                'g.categoria',
                'g.monto',
                DB::raw("TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) as empleado_nombre"),
            ])
            ->map(function ($row) {
                $row->fecha_gasto = $row->fecha_gasto ? Carbon::parse($row->fecha_gasto) : null;

                return $row;
            });
    }

    private function financialRows(string $desde, string $hasta)
    {
        return collect(DB::select(
            "
            SELECT fecha,
                   ROUND(SUM(ingresos), 2) as ingresos,
                   ROUND(SUM(egresos), 2) as egresos,
                   SUM(cobros_count)::int as cobros_count
            FROM (
                SELECT fecha_cobro::date as fecha, SUM(monto_pagado) as ingresos, 0::numeric as egresos, COUNT(*) as cobros_count
                FROM cobros
                WHERE fecha_cobro BETWEEN ? AND ? AND estado <> 'anulado'
                GROUP BY fecha_cobro::date
                UNION ALL
                SELECT fecha_ingreso::date as fecha, SUM(monto) as ingresos, 0::numeric as egresos, COUNT(*) as cobros_count
                FROM ingresos_administrativos
                WHERE fecha_ingreso BETWEEN ? AND ?
                GROUP BY fecha_ingreso::date
                UNION ALL
                SELECT fecha_gasto::date as fecha, 0::numeric as ingresos, SUM(monto) as egresos, 0 as cobros_count
                FROM gastos
                WHERE fecha_gasto BETWEEN ? AND ?
                GROUP BY fecha_gasto::date
            ) flow
            GROUP BY fecha
            ORDER BY fecha
            ",
            [$desde, $hasta, $desde, $hasta, $desde, $hasta]
        ))->map(fn ($row) => [
            'fecha' => $row->fecha,
            'ingresos' => (float) $row->ingresos,
            'egresos' => (float) $row->egresos,
            'cobros_count' => (int) $row->cobros_count,
        ]);
    }

    private function expensesByCategory(string $desde, string $hasta)
    {
        return DB::table('gastos')
            ->whereBetween('fecha_gasto', [$desde, $hasta])
            ->groupByRaw("COALESCE(NULLIF(categoria, ''), 'Sin categoria')")
            ->orderByDesc('total')
            ->get([
                DB::raw("COALESCE(NULLIF(categoria, ''), 'Sin categoria') as categoria"),
                DB::raw('ROUND(SUM(monto), 2) as total'),
            ])
            ->map(fn ($row) => [
                'categoria' => $row->categoria,
                'total' => (float) $row->total,
            ])
            ->values();
    }

    private function monthlyActivity()
    {
        $yearStart = now()->startOfYear()->toDateString();
        $yearEnd = now()->endOfYear()->toDateString();

        return collect(DB::select(
            "
            WITH months AS (SELECT generate_series(1, 12) as month)
            SELECT months.month,
                   COALESCE(c.total, 0) + COALESCE(ia.total, 0) as ingresos,
                   COALESCE(g.total, 0) as egresos,
                   COALESCE(s.total, 0) as usuarios,
                   COALESCE(l.total, 0) as lecturas
            FROM months
            LEFT JOIN (
                SELECT EXTRACT(MONTH FROM fecha_cobro)::int as month, SUM(monto_pagado) as total
                FROM cobros
                WHERE fecha_cobro BETWEEN ? AND ? AND estado <> 'anulado'
                GROUP BY EXTRACT(MONTH FROM fecha_cobro)::int
            ) c ON c.month = months.month
            LEFT JOIN (
                SELECT EXTRACT(MONTH FROM fecha_ingreso)::int as month, SUM(monto) as total
                FROM ingresos_administrativos
                WHERE fecha_ingreso BETWEEN ? AND ?
                GROUP BY EXTRACT(MONTH FROM fecha_ingreso)::int
            ) ia ON ia.month = months.month
            LEFT JOIN (
                SELECT EXTRACT(MONTH FROM fecha_gasto)::int as month, SUM(monto) as total
                FROM gastos
                WHERE fecha_gasto BETWEEN ? AND ?
                GROUP BY EXTRACT(MONTH FROM fecha_gasto)::int
            ) g ON g.month = months.month
            LEFT JOIN (
                SELECT EXTRACT(MONTH FROM created_at)::int as month, COUNT(*) as total
                FROM socios
                WHERE created_at BETWEEN ? AND ?
                GROUP BY EXTRACT(MONTH FROM created_at)::int
            ) s ON s.month = months.month
            LEFT JOIN (
                SELECT EXTRACT(MONTH FROM fecha_lectura)::int as month, COUNT(*) as total
                FROM lecturas
                WHERE fecha_lectura BETWEEN ? AND ?
                GROUP BY EXTRACT(MONTH FROM fecha_lectura)::int
            ) l ON l.month = months.month
            ORDER BY months.month
            ",
            [$yearStart, $yearEnd, $yearStart, $yearEnd, $yearStart, $yearEnd, $yearStart, $yearEnd, $yearStart, $yearEnd]
        ))->map(function ($row) {
            $monthDate = now()->copy()->month((int) $row->month)->startOfMonth();

            return [
                'mes' => ucfirst($monthDate->translatedFormat('M')),
                'ingresos' => round((float) $row->ingresos, 2),
                'egresos' => round((float) $row->egresos, 2),
                'usuarios' => (int) $row->usuarios,
                'lecturas' => (int) $row->lecturas,
            ];
        })->values();
    }

    private function currentMonthStats(?int $periodoId): object
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $periodFilter = $periodoId ? ' AND id_periodo = ?' : '';
        $bindings = [$monthStart, $monthEnd, $monthStart, $monthEnd, $monthStart, $monthEnd];

        if ($periodoId) {
            $bindings[] = $periodoId;
        }

        return DB::selectOne(
            "
            SELECT
                (SELECT COUNT(*) FROM socios WHERE created_at BETWEEN ? AND ?) as nuevos_socios_mes,
                (SELECT COUNT(*) FROM lecturas WHERE fecha_lectura BETWEEN ? AND ?) as lecturas_mes,
                (SELECT COUNT(*) * 30 FROM facturas WHERE fecha_emision BETWEEN ? AND ? AND consumo_m3 > 30 {$periodFilter}) as multas_mes
            ",
            $bindings
        );
    }

    private function expenseSegments($categories, float $total)
    {
        $colors = ['#ff7a1a', '#475569', '#fbbf24', '#22c55e', '#0ea5e9', '#ef4444', '#8b5cf6'];

        if ($total <= 0 || $categories->isEmpty()) {
            return collect([[
                'categoria' => 'Sin gastos',
                'total' => 0,
                'percent' => 100,
                'color' => '#e5e7eb',
            ]]);
        }

        return $categories->values()->map(fn ($item, $index) => [
            'categoria' => $item['categoria'],
            'total' => (float) $item['total'],
            'percent' => round(((float) $item['total'] / $total) * 100, 2),
            'color' => $colors[$index % count($colors)],
        ]);
    }

    private function pieGradient($segments): string
    {
        $cursor = 0;

        return $segments->map(function ($segment) use (&$cursor) {
            $start = $cursor;
            $cursor += (float) $segment['percent'];

            return "{$segment['color']} {$start}% {$cursor}%";
        })->implode(', ');
    }

    private function periodOptions()
    {
        return OperationalCache::rememberDomain('billing', 'reportes.periodos', fn () => PeriodoFacturacion::query()
            ->select(['id_periodo', 'nombre', 'fecha_inicio'])
            ->orderByDesc('fecha_inicio')
            ->get());
    }
}
