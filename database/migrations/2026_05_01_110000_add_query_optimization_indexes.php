<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_estado ON facturas (id_socio, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_facturas_socio_fecha_emision ON facturas (id_socio, fecha_emision DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_cobros_factura_estado ON cobros (id_factura, estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_lecturas_medidor_fecha ON lecturas (id_medidor, fecha_lectura DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_socios_estado_numero ON socios (estado, numero_socio)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_socios_estado_numero');
        DB::statement('DROP INDEX IF EXISTS idx_lecturas_medidor_fecha');
        DB::statement('DROP INDEX IF EXISTS idx_cobros_factura_estado');
        DB::statement('DROP INDEX IF EXISTS idx_facturas_socio_fecha_emision');
        DB::statement('DROP INDEX IF EXISTS idx_facturas_socio_estado');
    }
};
