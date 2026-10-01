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

        foreach ($this->indexes() as $statement) {
            $this->safeStatement($statement);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([
            'idx_cobros_estado_requested',
            'idx_cobros_id_factura_requested',
            'idx_lecturas_id_medidor_fecha_requested',
            'idx_lecturas_id_medidor_requested',
            'idx_medidores_numero_serie_requested',
            'idx_medidores_estado_requested',
            'idx_medidores_id_socio_requested',
            'idx_facturas_id_periodo_requested',
            'idx_facturas_id_socio_estado_requested',
            'idx_facturas_id_socio_requested',
            'idx_personas_cedula_identidad_requested',
            'idx_socios_id_sector_requested',
            'idx_socios_estado_requested',
            'idx_socios_numero_socio_requested',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }

    private function indexes(): array
    {
        return [
            'CREATE INDEX IF NOT EXISTS idx_socios_numero_socio_requested ON socios (numero_socio)',
            'CREATE INDEX IF NOT EXISTS idx_socios_estado_requested ON socios (estado)',
            'CREATE INDEX IF NOT EXISTS idx_socios_id_sector_requested ON socios (id_sector)',
            'CREATE INDEX IF NOT EXISTS idx_personas_cedula_identidad_requested ON personas (cedula_identidad)',
            'CREATE INDEX IF NOT EXISTS idx_facturas_id_socio_requested ON facturas (id_socio)',
            'CREATE INDEX IF NOT EXISTS idx_facturas_id_socio_estado_requested ON facturas (id_socio, estado)',
            'CREATE INDEX IF NOT EXISTS idx_facturas_id_periodo_requested ON facturas (id_periodo)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_id_socio_requested ON medidores (id_socio)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_estado_requested ON medidores (estado)',
            'CREATE INDEX IF NOT EXISTS idx_medidores_numero_serie_requested ON medidores (numero_serie)',
            'CREATE INDEX IF NOT EXISTS idx_lecturas_id_medidor_requested ON lecturas (id_medidor)',
            'CREATE INDEX IF NOT EXISTS idx_lecturas_id_medidor_fecha_requested ON lecturas (id_medidor, fecha_lectura DESC)',
            'CREATE INDEX IF NOT EXISTS idx_cobros_id_factura_requested ON cobros (id_factura)',
            'CREATE INDEX IF NOT EXISTS idx_cobros_estado_requested ON cobros (estado)',
        ];
    }

    private function safeStatement(string $statement): void
    {
        try {
            DB::statement($statement);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
};
