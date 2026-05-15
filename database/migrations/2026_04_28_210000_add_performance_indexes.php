<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_fecha_emision_id ON facturas (fecha_emision DESC, id_factura DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_socios_created_at ON socios (created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_medidores_socio_estado ON medidores (id_socio, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_periodos_fecha_inicio ON periodos_facturacion (fecha_inicio DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_facturas_fecha_emision_id');
        DB::statement('DROP INDEX IF EXISTS idx_socios_created_at');
        DB::statement('DROP INDEX IF EXISTS idx_medidores_socio_estado');
        DB::statement('DROP INDEX IF EXISTS idx_periodos_fecha_inicio');
    }
};
