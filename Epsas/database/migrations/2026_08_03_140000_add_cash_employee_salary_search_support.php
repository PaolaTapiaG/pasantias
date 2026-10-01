<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (! Schema::hasColumn('empleados', 'salario_base')) {
                $table->decimal('salario_base', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('empleados', 'bono_mensual')) {
                $table->decimal('bono_mensual', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('empleados', 'aguinaldo_anual')) {
                $table->decimal('aguinaldo_anual', 12, 2)->default(0);
            }
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->safeStatement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->indexes() as $statement) {
            $this->safeStatement($statement);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach ($this->indexNames() as $index) {
                $this->safeStatement("DROP INDEX IF EXISTS {$index}");
            }
        }

        Schema::table('empleados', function (Blueprint $table) {
            foreach (['salario_base', 'bono_mensual', 'aguinaldo_anual'] as $column) {
                if (Schema::hasColumn('empleados', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexes(): array
    {
        return [
            "CREATE INDEX IF NOT EXISTS idx_facturas_caja_pending ON facturas (id_socio, fecha_emision, id_factura) WHERE estado IN ('pendiente', 'vencida', 'parcial')",
            'CREATE INDEX IF NOT EXISTS idx_cobros_factura_estado_amount ON cobros (id_factura, estado, monto_pagado)',
            'CREATE INDEX IF NOT EXISTS idx_cobros_employee_date_state ON cobros (id_empleado, fecha_cobro DESC, estado)',
            'CREATE INDEX IF NOT EXISTS idx_empleados_role_estado_ingreso ON empleados (id_rol, estado, fecha_ingreso DESC, id_empleado DESC)',
            'CREATE INDEX IF NOT EXISTS idx_gastos_categoria_fecha ON gastos (categoria, fecha_gasto DESC, id_gasto DESC)',
            "CREATE INDEX IF NOT EXISTS idx_personas_nombre_completo_trgm ON personas USING gin ((LOWER(TRIM(COALESCE(nombres, '') || ' ' || COALESCE(apellidos, '')))) gin_trgm_ops)",
            'CREATE INDEX IF NOT EXISTS idx_roles_nombre_lower ON roles (LOWER(nombre))',
        ];
    }

    private function indexNames(): array
    {
        return [
            'idx_facturas_caja_pending',
            'idx_cobros_factura_estado_amount',
            'idx_cobros_employee_date_state',
            'idx_empleados_role_estado_ingreso',
            'idx_gastos_categoria_fecha',
            'idx_personas_nombre_completo_trgm',
            'idx_roles_nombre_lower',
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
