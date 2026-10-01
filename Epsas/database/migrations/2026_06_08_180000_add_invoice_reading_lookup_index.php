<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasColumn('facturas', 'id_lectura')) {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_id_lectura_lookup ON facturas (id_lectura)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_facturas_id_lectura_lookup');
    }
};
