<?php

namespace App\Services;

use App\Models\CierreCaja;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CashRegisterQuery
{
    public function summary(int $employeeId, bool $cached = true): array
    {
        $callback = function () use ($employeeId): array {
            $hasPaymentOrigin = Cache::remember(
                'schema:cobros:origen_pago',
                now()->addHours(12),
                fn () => Schema::hasColumn('cobros', 'origen_pago')
            );
            $query = DB::table('cobros as c')
                ->leftJoin('metodos_pago as mp', 'mp.id_metodo_pago', '=', 'c.id_metodo_pago')
                ->where('c.id_empleado', $employeeId)
                ->whereDate('c.fecha_cobro', today())
                ->where('c.estado', '<>', 'anulado');

            if ($hasPaymentOrigin) {
                $query->where(fn ($builder) => $builder->whereNull('c.origen_pago')->orWhere('c.origen_pago', '<>', 'online'));
            }

            $row = $query
                ->selectRaw('COUNT(*) as cantidad_cobros')
                ->selectRaw("COALESCE(SUM(c.monto_pagado) FILTER (WHERE LOWER(COALESCE(mp.nombre, '')) = 'efectivo'), 0) as efectivo")
                ->selectRaw("COALESCE(SUM(c.monto_pagado) FILTER (WHERE LOWER(COALESCE(mp.nombre, '')) IN ('qr', 'qr oficina')), 0) as qr")
                ->selectRaw("COALESCE(SUM(c.monto_pagado) FILTER (WHERE LOWER(COALESCE(mp.nombre, '')) = 'transferencia'), 0) as transferencia")
                ->selectRaw('COALESCE(SUM(c.monto_pagado), 0) as total')
                ->first();

            return [
                'cantidad_cobros' => (int) ($row->cantidad_cobros ?? 0),
                'efectivo' => round((float) ($row->efectivo ?? 0), 2),
                'qr' => round((float) ($row->qr ?? 0), 2),
                'transferencia' => round((float) ($row->transferencia ?? 0), 2),
                'total' => round((float) ($row->total ?? 0), 2),
            ];
        };

        return $cached
            ? Cache::remember($this->summaryKey($employeeId), now()->addMinutes(10), $callback)
            : $callback();
    }

    public function current(int $employeeId): ?CierreCaja
    {
        $payload = Cache::remember($this->currentKey($employeeId), now()->addMinutes(10), fn () => [
            'cash' => CierreCaja::query()
                ->where('id_empleado', $employeeId)
                ->whereDate('fecha_caja', today())
                ->first(),
        ]);

        return $payload['cash'] ?? null;
    }

    public function forget(int $employeeId): void
    {
        Cache::forget($this->summaryKey($employeeId));
        Cache::forget($this->currentKey($employeeId));
    }

    private function summaryKey(int $employeeId): string
    {
        return 'secretaria:operations:cash:'.$employeeId.':'.today()->toDateString();
    }

    private function currentKey(int $employeeId): string
    {
        return 'secretaria:operations:current-cash:'.$employeeId.':'.today()->toDateString();
    }
}
