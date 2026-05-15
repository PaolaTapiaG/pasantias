<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/schema.sql')));
        DB::unprepared(file_get_contents(database_path('sql/triggers.sql')));
        DB::unprepared(file_get_contents(database_path('sql/rls.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS v_cobros_periodo_actual;
DROP VIEW IF EXISTS v_saldo_socios;
DROP VIEW IF EXISTS v_empleados;
DROP VIEW IF EXISTS v_socios;

DROP TRIGGER IF EXISTS trg_tarifa_factura ON facturas;
DROP TRIGGER IF EXISTS trg_validar_pago ON cobros;
DROP TRIGGER IF EXISTS trg_actualizar_estado_factura ON cobros;
DROP TRIGGER IF EXISTS trg_historial_cobro ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_facturas ON facturas;
DROP TRIGGER IF EXISTS trg_auditoria_cobros ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_socios ON socios;
DROP TRIGGER IF EXISTS trg_auditoria_tarifas ON tarifas;

DROP FUNCTION IF EXISTS get_mi_rol();
DROP FUNCTION IF EXISTS tiene_rol(VARIADIC TEXT[]);
DROP FUNCTION IF EXISTS get_mi_empleado_id();
DROP FUNCTION IF EXISTS guardar_tarifa_factura();
DROP FUNCTION IF EXISTS validar_pago_factura();
DROP FUNCTION IF EXISTS actualizar_estado_factura();
DROP FUNCTION IF EXISTS registrar_historial_cobro();
DROP FUNCTION IF EXISTS marcar_facturas_vencidas();
DROP FUNCTION IF EXISTS auditoria_trigger();

DROP TABLE IF EXISTS auditoria CASCADE;
DROP TABLE IF EXISTS notificaciones CASCADE;
DROP TABLE IF EXISTS historial_pagos CASCADE;
DROP TABLE IF EXISTS cobros CASCADE;
DROP TABLE IF EXISTS facturas CASCADE;
DROP TABLE IF EXISTS lecturas CASCADE;
DROP TABLE IF EXISTS periodos_facturacion CASCADE;
DROP TABLE IF EXISTS medidores CASCADE;
DROP TABLE IF EXISTS empleados CASCADE;
DROP TABLE IF EXISTS socios CASCADE;
DROP TABLE IF EXISTS metodos_pago CASCADE;
DROP TABLE IF EXISTS tarifas CASCADE;
DROP TABLE IF EXISTS sectores CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS personas CASCADE;
SQL);
    }
};
