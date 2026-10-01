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
            'idx_personas_cedula_identidad_requested',
            'idx_personas_ci',
            'idx_personas_ci_exact',
            'idx_personas_email_lookup',
            'idx_medidores_id_socio_requested',
            'idx_medidores_numero_serie_requested',
            'idx_medidores_serie_exact',
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
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_personas_cedula_identidad_requested ON public.personas (cedula_identidad)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_personas_ci ON public.personas (cedula_identidad)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_personas_ci_exact ON public.personas (cedula_identidad)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_personas_email_lookup ON public.personas (LOWER(email))',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_medidores_id_socio_requested ON public.medidores (id_socio)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_medidores_numero_serie_requested ON public.medidores (numero_serie)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_medidores_serie_exact ON public.medidores (numero_serie)',
        ] as $statement) {
            DB::statement($statement);
        }
    }
};
