<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BillingIntegrityAudit
{
    public function run(): array
    {
        $payments = DB::table('cobros')
            ->selectRaw("id_factura, COALESCE(SUM(CASE WHEN estado <> 'anulado' THEN monto_pagado ELSE 0 END), 0) as pagado")
            ->groupBy('id_factura');

        $invoices = DB::table('facturas as f')
            ->leftJoinSub($payments, 'cp', fn ($join) => $join->on('cp.id_factura', '=', 'f.id_factura'));

        return [
            'invoice_total_mismatches' => (clone $invoices)
                ->whereRaw('ABS(f.total - (f.monto_consumo + f.cargo_fijo + f.recargo_mora - f.descuentos)) > 0.009')
                ->count(),
            'negative_invoice_values' => DB::table('facturas')
                ->whereRaw('consumo_m3 < 0 OR monto_consumo < 0 OR cargo_fijo < 0 OR recargo_mora < 0 OR descuentos < 0 OR total < 0')
                ->count(),
            'duplicate_reading_invoices' => DB::table('facturas')
                ->whereNotNull('id_lectura')
                ->select('id_lectura')
                ->groupBy('id_lectura')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
            'duplicate_invoice_numbers' => DB::table('facturas')
                ->select('numero_factura')
                ->groupBy('numero_factura')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
            'overpaid_invoices' => (clone $invoices)
                ->whereRaw('COALESCE(cp.pagado, 0) > f.total + 0.009')
                ->count(),
            'paid_invoices_with_balance' => (clone $invoices)
                ->where('f.estado', 'pagada')
                ->whereRaw('COALESCE(cp.pagado, 0) < f.total - 0.009')
                ->count(),
            'open_invoices_without_balance' => (clone $invoices)
                ->whereIn('f.estado', ['pendiente', 'parcial', 'vencida'])
                ->whereRaw('COALESCE(cp.pagado, 0) >= f.total - 0.009')
                ->count(),
            'non_positive_payments' => DB::table('cobros')->where('monto_pagado', '<=', 0)->count(),
            'orphan_payments' => DB::table('cobros as c')
                ->leftJoin('facturas as f', 'f.id_factura', '=', 'c.id_factura')
                ->whereNull('f.id_factura')
                ->count(),
        ];
    }

    public function isClean(array $checks): bool
    {
        return collect($checks)->every(fn ($value) => (int) $value === 0);
    }
}
