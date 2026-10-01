<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Factura;
use App\Models\Lectura;
use App\Models\PeriodoFacturacion;
use App\Support\OperationalCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BillingAutomationService
{
    private array $periodsByMonth = [];

    public function __construct(private WaterBillingService $waterBilling)
    {
    }

    public function ensureCurrentInvoices(?int $idSocio = null, bool $force = false): array
    {
        $startedAt = microtime(true);
        $scope = $idSocio ? "socio:{$idSocio}" : 'global';
        $guardKey = 'billing:auto:' . $scope . ':' . now()->toDateString();

        if (!$force && Cache::has($guardKey)) {
            $this->logTiming('billing.ensureCurrentInvoices.cached', $startedAt, [
                'scope' => $scope,
                'id_socio' => $idSocio,
                'guard' => $guardKey,
            ]);

            return [
                'cached' => true,
                'created' => 0,
                'skipped' => 0,
            ];
        }

        if (!$force && !$idSocio) {
            $cutoff = now()->subMonthNoOverflow()->endOfMonth()->startOfDay();

            if ($this->globalIsCurrent($cutoff)) {
                Cache::put($guardKey, true, now()->addHours(12));
                $this->logTiming('billing.ensureCurrentInvoices.up_to_date.global', $startedAt, [
                    'scope' => $scope,
                    'id_socio' => $idSocio,
                ]);

                return [
                    'cached' => false,
                    'created' => 0,
                    'skipped' => 0,
                    'up_to_date' => true,
                ];
            }
        }

        if (!$force && $idSocio && $this->socioIsCurrent($idSocio)) {
            Cache::put($guardKey, true, now()->addHours(2));
            $this->logTiming('billing.ensureCurrentInvoices.up_to_date.socio', $startedAt, [
                'scope' => $scope,
                'id_socio' => $idSocio,
            ]);

            return [
                'cached' => false,
                'created' => 0,
                'skipped' => 0,
                'up_to_date' => true,
            ];
        }

        $result = DB::transaction(function () use ($idSocio) {
            $this->lockBillingAutomation();

            return $this->syncInvoices($idSocio);
        }, 3);
        Cache::put($guardKey, true, now()->addHours($idSocio ? 2 : 12));
        $this->logTiming('billing.ensureCurrentInvoices', $startedAt, [
            'scope' => $scope,
            'id_socio' => $idSocio,
            'created' => $result['created'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
        ]);

        return $result + ['cached' => false];
    }

    public function ensureSocioInvoices(int $idSocio, bool $force = false): array
    {
        return $this->ensureCurrentInvoices($idSocio, $force);
    }

    private function logTiming(string $label, float $start, array $context = []): void
    {
        logger()->info($label, array_merge($context, [
            'ms' => round((microtime(true) - $start) * 1000, 2),
        ]));
    }

    public function monthlyPeriodFor(Carbon $date): PeriodoFacturacion
    {
        $this->warmPeriodCache();
        $key = $date->copy()->startOfMonth()->format('Y-m');

        if (isset($this->periodsByMonth[$key])) {
            return $this->periodsByMonth[$key];
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $period = PeriodoFacturacion::create([
            'nombre' => $this->periodName($start),
            'fecha_inicio' => $start->toDateString(),
            'fecha_fin' => $end->toDateString(),
            'cerrado' => false,
        ]);

        $this->periodsByMonth[$key] = $period;

        return $period;
    }

    public function nextChargeStartForSocio(int $idSocio, ?string $fallbackDate = null): Carbon
    {
        $lastEnd = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->where('f.id_socio', $idSocio)
            ->max(DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin)'));

        if ($lastEnd) {
            return Carbon::parse($lastEnd)->addDay()->startOfDay();
        }

        return Carbon::parse($fallbackDate ?: now()->toDateString())->startOfDay();
    }

    private function syncInvoices(?int $idSocio = null): array
    {
        $cutoff = now()->subMonthNoOverflow()->endOfMonth()->startOfDay();
        $employeeId = $this->systemEmployeeId();

        if (!$employeeId) {
            return [
                'created' => 0,
                'skipped' => 0,
                'reason' => 'No existe un empleado activo para registrar lecturas automaticas.',
            ];
        }

        $socios = $this->billableSocios($idSocio);

        if ($socios->isEmpty()) {
            return [
                'created' => 0,
                'skipped' => 0,
            ];
        }

        $context = $this->billingContext($socios, $cutoff);
        $created = 0;
        $skipped = 0;
        $nextInvoiceSequence = $this->nextInvoiceSequence();

        foreach ($socios as $socio) {
            $result = $this->syncSocio($socio, $cutoff, $employeeId, $nextInvoiceSequence, $context);
            $created += $result['created'];
            $skipped += $result['skipped'];
        }

        if ($created > 0) {
            $this->flushBillingCaches();
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    private function billableSocios(?int $idSocio): Collection
    {
        return DB::table('socios as s')
            ->join('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->join('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
            ->where('s.estado', 'activo')
            ->when($idSocio, fn ($query) => $query->where('s.id_socio', $idSocio))
            ->orderBy('s.id_socio')
            ->get([
                's.id_socio',
                's.numero_socio',
                's.fecha_registro',
                'm.id_medidor',
                'm.fecha_instalacion',
                't.precio_m3_base',
                't.consumo_minimo_m3',
                't.cargo_fijo as tarifa_cargo_fijo',
            ]);
    }

    private function billingContext(Collection $socios, Carbon $cutoff): array
    {
        $startedAt = microtime(true);
        $socioIds = $socios->pluck('id_socio')->map(fn ($id) => (int) $id)->values();
        $medidorIds = $socios->pluck('id_medidor')->filter()->map(fn ($id) => (int) $id)->values();

        $lastEnds = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->whereIn('f.id_socio', $socioIds)
            ->groupBy('f.id_socio')
            ->selectRaw('f.id_socio, MAX(COALESCE(f.fecha_fin_cobro, pf.fecha_fin)) as last_end')
            ->pluck('last_end', 'id_socio')
            ->all();

        $invoicedRows = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->whereIn('f.id_socio', $socioIds)
            ->get([
                'f.id_socio',
                DB::raw('COALESCE(f.fecha_inicio_cobro, pf.fecha_inicio) as inicio'),
                DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin) as fin'),
            ]);

        $invoicedMonths = [];
        foreach ($invoicedRows as $row) {
            if (!$row->inicio || !$row->fin) {
                continue;
            }

            $cursor = Carbon::parse($row->inicio)->startOfMonth();
            $end = Carbon::parse($row->fin)->startOfMonth();
            $idSocio = (int) $row->id_socio;

            while ($cursor->lte($end)) {
                $invoicedMonths[$idSocio][$cursor->format('Y-m')] = true;
                $cursor->addMonthNoOverflow();
            }
        }

        $readings = Lectura::query()
            ->select([
                'id_lectura',
                'fecha_lectura',
                'lectura_anterior',
                'lectura_actual',
                'consumo_m3',
                'id_medidor',
            ])
            ->withCount('facturas')
            ->whereIn('id_medidor', $medidorIds)
            ->whereDate('fecha_lectura', '<=', $cutoff->toDateString())
            ->orderBy('id_medidor')
            ->orderBy('fecha_lectura')
            ->orderBy('id_lectura')
            ->get()
            ->groupBy('id_medidor');

        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        $pendingBalances = DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->whereIn('f.id_socio', $socioIds)
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->groupBy('f.id_socio')
            ->selectRaw('f.id_socio, ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 0), 2) as saldo')
            ->pluck('saldo', 'id_socio')
            ->all();

        $this->logTiming('billing.billingContext', $startedAt, [
            'socios' => $socios->count(),
            'socio_ids' => $socioIds->all(),
        ]);

        return [
            'lastEnds' => $lastEnds,
            'invoicedMonths' => $invoicedMonths,
            'readings' => $readings,
            'pendingBalances' => $pendingBalances,
        ];
    }

    private function syncSocio(object $socio, Carbon $cutoff, int $employeeId, int &$nextInvoiceSequence, array $context): array
    {
        $startedAt = microtime(true);
        $created = 0;
        $skipped = 0;
        $idSocio = (int) $socio->id_socio;
        $idMedidor = (int) $socio->id_medidor;
        $fallback = $socio->fecha_instalacion ?: $socio->fecha_registro ?: now()->toDateString();
        $lastEnd = $context['lastEnds'][$idSocio] ?? null;
        $chargeStart = $lastEnd
            ? Carbon::parse($lastEnd)->addDay()->startOfDay()
            : Carbon::parse($fallback)->startOfDay();

        if ($chargeStart->gt($cutoff)) {
            return compact('created', 'skipped');
        }

        $invoicedMonths = $context['invoicedMonths'][$idSocio] ?? [];
        $readings = $context['readings'][$idMedidor] ?? collect();
        $runningBalance = (float) ($context['pendingBalances'][$idSocio] ?? 0);
        $now = now();
        $invoiceRows = [];
        $cursor = $chargeStart->copy()->startOfMonth();

        while ($cursor->lte($cutoff)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $monthKey = $monthStart->format('Y-m');
            $coverageStart = $chargeStart->isSameMonth($monthStart) ? $chargeStart->copy() : $monthStart->copy();
            $coverageEnd = $monthEnd->lte($cutoff) ? $monthEnd->copy() : $cutoff->copy();

            if ($coverageEnd->lt($coverageStart) || isset($invoicedMonths[$monthKey])) {
                $cursor->addMonthNoOverflow();
                continue;
            }

            $period = $this->monthlyPeriodFor($monthStart);
            $lectura = $this->readingForInvoice($readings, $idMedidor, $coverageStart, $coverageEnd, $employeeId);

            if (!$lectura) {
                $skipped++;
                $cursor->addMonthNoOverflow();
                continue;
            }

            $breakdown = $this->waterBilling->breakdown((float) $lectura->consumo_m3, [
                'included_m3' => (float) $socio->consumo_minimo_m3,
                'fixed_charge' => (float) $socio->tarifa_cargo_fijo,
                'excess_rate' => (float) $socio->precio_m3_base,
            ]);
            $recargoMora = ($runningBalance > 0 ? round($runningBalance * 0.02, 2) : 0) + $breakdown['cutoff_penalty'];
            $invoiceTotal = round($breakdown['water_charge'] + $breakdown['sewer_fixed_charge'] + $recargoMora, 2);
            $runningBalance = round($runningBalance + $invoiceTotal, 2);

            $invoiceRows[] = [
                'numero_factura' => $this->nextInvoiceNumber($nextInvoiceSequence),
                'fecha_emision' => $now->toDateString(),
                'fecha_inicio_cobro' => $coverageStart->toDateString(),
                'fecha_fin_cobro' => $coverageEnd->toDateString(),
                'consumo_m3' => $lectura->consumo_m3,
                'monto_consumo' => $breakdown['water_charge'],
                'cargo_fijo' => $breakdown['sewer_fixed_charge'],
                'recargo_mora' => $recargoMora,
                'descuentos' => 0,
                'precio_m3_aplicado' => $breakdown['excess_rate'],
                'cargo_fijo_aplicado' => $breakdown['fixed_charge'],
                'consumo_minimo_m3_aplicado' => $breakdown['included_m3'],
                'umbral_corte_m3_aplicado' => $breakdown['cutoff_threshold_m3'],
                'tarifa_reconexion_aplicada' => $breakdown['reconnection_fee'],
                'estado' => $coverageEnd->copy()->addDays(30)->isPast() ? 'vencida' : 'pendiente',
                'id_socio' => $socio->id_socio,
                'id_lectura' => $lectura->id_lectura,
                'id_periodo' => $period->id_periodo,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $invoicedMonths[$monthKey] = true;
            $this->markReadingAsUsed($readings, (int) $lectura->id_lectura);
            $created++;
            $cursor->addMonthNoOverflow();
        }

        if ($invoiceRows !== []) {
            DB::table('facturas')->insert($invoiceRows);
        }

        $this->logTiming('billing.syncSocio', $startedAt, [
            'id_socio' => $idSocio,
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return compact('created', 'skipped');
    }

    private function lastInvoiceEnd(int $idSocio): ?string
    {
        return DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->where('f.id_socio', $idSocio)
            ->max(DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin)'));
    }

    private function socioIsCurrent(int $idSocio): bool
    {
        $lastEnd = $this->lastInvoiceEnd($idSocio);

        if (!$lastEnd) {
            return false;
        }

        return Carbon::parse($lastEnd)->gte(now()->subMonthNoOverflow()->endOfMonth()->startOfDay());
    }

    private function globalIsCurrent(Carbon $cutoff): bool
    {
        $startedAt = microtime(true);
        $billable = DB::table('socios as s')
            ->join('medidores as m', function ($join) {
                $join->on('m.id_socio', '=', 's.id_socio')
                    ->where('m.estado', '=', 'activo');
            })
            ->join('tarifas as t', 't.id_tarifa', '=', 's.id_tarifa')
            ->where('s.estado', 'activo')
            ->select('s.id_socio');

        $latestInvoices = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->groupBy('f.id_socio')
            ->selectRaw('f.id_socio, MAX(COALESCE(f.fecha_fin_cobro, pf.fecha_fin)) as last_end');

        $outdatedExists = DB::query()
            ->fromSub($billable, 'b')
            ->leftJoinSub($latestInvoices, 'lf', fn ($join) => $join->on('lf.id_socio', '=', 'b.id_socio'))
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('lf.last_end')
                    ->orWhereDate('lf.last_end', '<', $cutoff->toDateString());
            })
            ->exists();

        $this->logTiming('billing.globalIsCurrent', $startedAt, [
            'cutoff' => $cutoff->toDateString(),
            'outdated_exists' => $outdatedExists,
        ]);

        return !$outdatedExists;
    }

    private function invoicedMonths(int $idSocio): array
    {
        $months = [];
        $rows = DB::table('facturas as f')
            ->leftJoin('periodos_facturacion as pf', 'pf.id_periodo', '=', 'f.id_periodo')
            ->where('f.id_socio', $idSocio)
            ->get([
                DB::raw('COALESCE(f.fecha_inicio_cobro, pf.fecha_inicio) as inicio'),
                DB::raw('COALESCE(f.fecha_fin_cobro, pf.fecha_fin) as fin'),
            ]);

        foreach ($rows as $row) {
            if (!$row->inicio || !$row->fin) {
                continue;
            }

            $cursor = Carbon::parse($row->inicio)->startOfMonth();
            $end = Carbon::parse($row->fin)->startOfMonth();

            while ($cursor->lte($end)) {
                $months[$cursor->format('Y-m')] = true;
                $cursor->addMonthNoOverflow();
            }
        }

        return $months;
    }

    private function readingsForMedidor(int $idMedidor, Carbon $cutoff): Collection
    {
        return Lectura::query()
            ->select([
                'id_lectura',
                'fecha_lectura',
                'lectura_anterior',
                'lectura_actual',
                'consumo_m3',
                'id_medidor',
            ])
            ->withCount('facturas')
            ->where('id_medidor', $idMedidor)
            ->whereDate('fecha_lectura', '<=', $cutoff->toDateString())
            ->orderBy('fecha_lectura')
            ->orderBy('id_lectura')
            ->get();
    }

    private function readingForInvoice(Collection $readings, int $idMedidor, Carbon $coverageStart, Carbon $coverageEnd, int $employeeId): ?Lectura
    {
        $pendingReading = $readings
            ->filter(function (Lectura $reading) use ($coverageStart, $coverageEnd) {
                $date = Carbon::parse($reading->fecha_lectura);

                return (int) ($reading->facturas_count ?? 0) === 0
                    && $date->betweenIncluded($coverageStart, $coverageEnd);
            })
            ->sortByDesc('fecha_lectura')
            ->first();

        if ($pendingReading) {
            return $pendingReading;
        }

        $readingDate = $coverageEnd->toDateString();
        $sameDayReading = $readings->first(function (Lectura $reading) use ($readingDate) {
            return $reading->fecha_lectura->toDateString() === $readingDate;
        });

        if ($sameDayReading) {
            return (int) ($sameDayReading->facturas_count ?? 0) > 0 ? null : $sameDayReading;
        }

        $lastReading = $readings
            ->filter(fn (Lectura $reading) => $reading->fecha_lectura->toDateString() <= $readingDate)
            ->sortByDesc('fecha_lectura')
            ->first();

        $meterValue = (float) ($lastReading?->lectura_actual ?? 0);
        $reading = Lectura::create([
            'fecha_lectura' => $readingDate,
            'lectura_anterior' => $meterValue,
            'lectura_actual' => $meterValue,
            'observaciones' => 'Lectura automatica para facturacion mensual sin consumo registrado. Se cobra cargo fijo.',
            'id_medidor' => $idMedidor,
            'id_empleado' => $employeeId,
        ]);

        $reading->setAttribute('consumo_m3', '0.00');
        $reading->setAttribute('facturas_count', 0);
        $readings->push($reading);

        return $reading;
    }

    private function markReadingAsUsed(Collection $readings, int $idLectura): void
    {
        $reading = $readings->firstWhere('id_lectura', $idLectura);

        if ($reading) {
            $reading->setAttribute('facturas_count', 1);
        }
    }

    private function pendingBalance(int $idSocio): float
    {
        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        return (float) DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'))
            ->where('f.id_socio', $idSocio)
            ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
            ->selectRaw('ROUND(COALESCE(SUM(GREATEST(f.total - COALESCE(cp.pagado, 0), 0)), 0), 2) as saldo')
            ->value('saldo');
    }

    private function systemEmployeeId(): ?int
    {
        return Cache::remember('billing:auto:employee-id', now()->addHours(6), function () {
            return Empleado::query()
                ->where('estado', 'activo')
                ->orderBy('id_empleado')
                ->value('id_empleado');
        });
    }

    private function nextInvoiceNumber(int &$sequence): string
    {
        $number = 'FAC-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        $sequence++;

        return $number;
    }

    private function nextInvoiceSequence(): int
    {
        $maxNumber = DB::table('facturas')
            ->where('numero_factura', '~', '^FAC-[0-9]+$')
            ->selectRaw("MAX((regexp_replace(numero_factura, '^FAC-', ''))::int) as max_number")
            ->value('max_number');

        return ((int) $maxNumber) + 1;
    }

    private function warmPeriodCache(): void
    {
        if ($this->periodsByMonth !== []) {
            return;
        }

        PeriodoFacturacion::query()
            ->select(['id_periodo', 'nombre', 'fecha_inicio', 'fecha_fin', 'cerrado'])
            ->orderByDesc('fecha_fin')
            ->get()
            ->each(function (PeriodoFacturacion $period) {
                if (!$period->fecha_fin) {
                    return;
                }

                $key = $period->fecha_fin->copy()->startOfMonth()->format('Y-m');
                $this->periodsByMonth[$key] ??= $period;
            });
    }

    private function periodName(Carbon $date): string
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    private function flushBillingCaches(): void
    {
        Cache::forget('facturas.totales');
        Cache::forget('facturas.billing_candidates');
        Cache::forget('facturas.periodos');
        Cache::forget('tecnico:billing-signals');
        Cache::forget('tecnico:corte:open-socios');
        Cache::forget('tecnico:reconexion:open-socios');
        Cache::forget('tecnico:reconexion:latest-cuts');
        Cache::forget('api.dashboard.tecnico');
        OperationalCache::bumpDomain('billing');
    }

    private function lockBillingAutomation(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select("SELECT pg_advisory_xact_lock(hashtext('billing-automation'))");
            DB::select("SELECT pg_advisory_xact_lock(hashtext('invoice-number-sequence'))");

            return;
        }

        DB::table('periodos_facturacion')->orderBy('id_periodo')->lockForUpdate()->value('id_periodo');
    }
}
