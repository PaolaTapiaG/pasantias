<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->decimal('consumo_minimo_m3_aplicado', 10, 2)->nullable();
            $table->decimal('umbral_corte_m3_aplicado', 10, 2)->nullable();
            $table->decimal('tarifa_reconexion_aplicada', 10, 2)->nullable();
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $settings = json_decode((string) DB::table('system_settings')->where('key', 'general')->value('value'), true) ?: [];
        $threshold = (float) ($settings['cutoff_threshold_m3'] ?? 30);
        $reconnectionFee = (float) ($settings['reconnection_fee'] ?? 30);

        DB::statement(
            'UPDATE facturas f
             SET consumo_minimo_m3_aplicado = t.consumo_minimo_m3,
                 umbral_corte_m3_aplicado = ?,
                 tarifa_reconexion_aplicada = ?
             FROM socios s
             JOIN tarifas t ON t.id_tarifa = s.id_tarifa
             WHERE s.id_socio = f.id_socio',
            [$threshold, $reconnectionFee]
        );

        DB::statement('ALTER TABLE facturas ALTER COLUMN consumo_minimo_m3_aplicado SET NOT NULL');
        DB::statement('ALTER TABLE facturas ALTER COLUMN umbral_corte_m3_aplicado SET NOT NULL');
        DB::statement('ALTER TABLE facturas ALTER COLUMN tarifa_reconexion_aplicada SET NOT NULL');
        DB::statement('ALTER TABLE facturas ADD CONSTRAINT chk_facturas_valores_no_negativos CHECK (consumo_m3 >= 0 AND monto_consumo >= 0 AND cargo_fijo >= 0 AND recargo_mora >= 0 AND descuentos >= 0 AND total >= 0 AND consumo_minimo_m3_aplicado >= 0 AND umbral_corte_m3_aplicado >= 0 AND tarifa_reconexion_aplicada >= 0)');

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION guardar_tarifa_factura()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
  SELECT t.precio_m3_base, t.cargo_fijo, t.consumo_minimo_m3
    INTO NEW.precio_m3_aplicado, NEW.cargo_fijo_aplicado, NEW.consumo_minimo_m3_aplicado
  FROM tarifas t
  JOIN socios s ON s.id_tarifa = t.id_tarifa
  WHERE s.id_socio = NEW.id_socio;

  RETURN NEW;
END;
$$;
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE facturas DROP CONSTRAINT IF EXISTS chk_facturas_valores_no_negativos');
        }

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'consumo_minimo_m3_aplicado',
                'umbral_corte_m3_aplicado',
                'tarifa_reconexion_aplicada',
            ]);
        });
    }
};
