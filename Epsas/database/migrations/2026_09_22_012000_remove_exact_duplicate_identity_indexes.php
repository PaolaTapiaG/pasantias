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
            'idx_empleados_persona',
            'idx_socios_persona',
            'idx_users_email_lower',
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
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empleados_persona ON public.empleados (id_persona)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_socios_persona ON public.socios (id_persona)',
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_email_lower ON public.users (LOWER(email))',
        ] as $statement) {
            DB::statement($statement);
        }
    }
};
