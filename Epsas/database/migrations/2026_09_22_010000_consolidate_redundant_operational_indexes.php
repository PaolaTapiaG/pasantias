<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'idx_cobros_id_factura_requested',
            'idx_cobros_factura_estado_amount',
            'idx_facturas_id_periodo_requested',
            'idx_facturas_id_socio_requested',
            'idx_facturas_id_socio_estado_requested',
            'idx_lecturas_id_medidor_requested',
            'idx_lecturas_id_medidor_fecha_requested',
            'idx_socios_numero',
            'idx_socios_numero_exact',
            'idx_socios_numero_socio_requested',
        ] as $indexName) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS public.{$indexName}");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobros_id_factura_requested ON public.cobros (id_factura)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_cobros_factura_estado_amount ON public.cobros (id_factura, estado, monto_pagado)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_facturas_id_periodo_requested ON public.facturas (id_periodo)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_facturas_id_socio_requested ON public.facturas (id_socio)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_facturas_id_socio_estado_requested ON public.facturas (id_socio, estado)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_lecturas_id_medidor_requested ON public.lecturas (id_medidor)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_lecturas_id_medidor_fecha_requested ON public.lecturas (id_medidor, fecha_lectura DESC)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_socios_numero ON public.socios (numero_socio)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_socios_numero_exact ON public.socios (numero_socio)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_socios_numero_socio_requested ON public.socios (numero_socio)',
        ] as $statement) {
            DB::statement($statement);
        }
    }
};
