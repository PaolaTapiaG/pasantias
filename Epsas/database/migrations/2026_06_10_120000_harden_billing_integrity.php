<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_facturas_id_lectura ON facturas (id_lectura) WHERE id_lectura IS NOT NULL');
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uq_ordenes_pago_referencia_activa ON ordenes_pago (LOWER(comprobante_referencia)) WHERE comprobante_referencia IS NOT NULL AND estado IN ('en_revision', 'aprobada')");
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_cobros_orden_factura ON cobros (id_orden_pago, id_factura) WHERE id_orden_pago IS NOT NULL');

        DB::statement('ALTER TABLE ordenes_pago ADD CONSTRAINT chk_ordenes_pago_total_positivo CHECK (total > 0)');
        DB::statement('ALTER TABLE cobros ADD CONSTRAINT chk_cobros_monto_pagado_positivo CHECK (monto_pagado > 0)');
        DB::statement('ALTER TABLE cobros ADD CONSTRAINT chk_cobros_monto_pendiente_no_negativo CHECK (monto_pendiente >= 0)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE ordenes_pago DROP CONSTRAINT IF EXISTS chk_ordenes_pago_total_positivo');
        DB::statement('ALTER TABLE cobros DROP CONSTRAINT IF EXISTS chk_cobros_monto_pagado_positivo');
        DB::statement('ALTER TABLE cobros DROP CONSTRAINT IF EXISTS chk_cobros_monto_pendiente_no_negativo');
        DB::statement('DROP INDEX IF EXISTS uq_cobros_orden_factura');
        DB::statement('DROP INDEX IF EXISTS uq_ordenes_pago_referencia_activa');
        DB::statement('DROP INDEX IF EXISTS uq_facturas_id_lectura');
    }
};
