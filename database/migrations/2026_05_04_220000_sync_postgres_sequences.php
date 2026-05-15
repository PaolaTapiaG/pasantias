<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncSequence('migrations', 'id');
        $this->syncSequence('personas', 'id_persona');
        $this->syncSequence('roles', 'id_rol');
        $this->syncSequence('sectores', 'id_sector');
        $this->syncSequence('tarifas', 'id_tarifa');
        $this->syncSequence('metodos_pago', 'id_metodo_pago');
        $this->syncSequence('empleados', 'id_empleado');
        $this->syncSequence('socios', 'id_socio');
        $this->syncSequence('medidores', 'id_medidor');
        $this->syncSequence('periodos_facturacion', 'id_periodo');
        $this->syncSequence('lecturas', 'id_lectura');
        $this->syncSequence('facturas', 'id_factura');
        $this->syncSequence('cobros', 'id_cobro');
        $this->syncSequence('historial_pagos', 'id_historial');
        $this->syncSequence('notificaciones', 'id_notificacion');
        $this->syncSequence('auditoria', 'id_auditoria');
    }

    public function down(): void
    {
        // No-op: sequence sync is derived from current data.
    }

    private function syncSequence(string $table, string $column): void
    {
        $sequence = DB::scalar(
            "select pg_get_serial_sequence(?, ?) as seq",
            [$table, $column]
        );

        if (!$sequence) {
            return;
        }

        $maxId = DB::table($table)->max($column) ?? 0;
        $value = max((int) $maxId, 1);

        DB::select('select setval(?::regclass, ?, true)', [$sequence, $value]);
    }
};
