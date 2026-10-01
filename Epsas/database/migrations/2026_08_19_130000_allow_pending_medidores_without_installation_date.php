<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medidores') || ! Schema::hasColumn('medidores', 'fecha_instalacion')) {
            return;
        }

        DB::statement('ALTER TABLE medidores ALTER COLUMN fecha_instalacion DROP NOT NULL');
        DB::statement('ALTER TABLE medidores ALTER COLUMN fecha_instalacion DROP DEFAULT');
    }

    public function down(): void
    {
        if (! Schema::hasTable('medidores') || ! Schema::hasColumn('medidores', 'fecha_instalacion')) {
            return;
        }

        DB::statement('UPDATE medidores SET fecha_instalacion = CURRENT_DATE WHERE fecha_instalacion IS NULL');
        DB::statement('ALTER TABLE medidores ALTER COLUMN fecha_instalacion SET DEFAULT CURRENT_DATE');
        DB::statement('ALTER TABLE medidores ALTER COLUMN fecha_instalacion SET NOT NULL');
    }
};
