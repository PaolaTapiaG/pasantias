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

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.auditoria_trigger()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
    v_registro JSONB;
    v_actor_empleado_id BIGINT;
    v_socio_id BIGINT;
    v_factura_id BIGINT;
    v_cobro_id BIGINT;
    v_tarifa_id BIGINT;
    v_auth_uid UUID;
BEGIN
    v_auth_uid := public.epsas_auth_uid();
    v_registro := CASE
        WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD)
        ELSE to_jsonb(NEW)
    END;

    SELECT e.id_empleado
      INTO v_actor_empleado_id
    FROM public.empleados e
    WHERE e.user_id = v_auth_uid
    LIMIT 1;

    IF TG_TABLE_NAME = 'socios' THEN
        v_socio_id := (v_registro->>'id_socio')::BIGINT;
        v_tarifa_id := (v_registro->>'id_tarifa')::BIGINT;
    ELSIF TG_TABLE_NAME = 'facturas' THEN
        v_factura_id := (v_registro->>'id_factura')::BIGINT;
        v_socio_id := (v_registro->>'id_socio')::BIGINT;
    ELSIF TG_TABLE_NAME = 'cobros' THEN
        v_cobro_id := (v_registro->>'id_cobro')::BIGINT;
        v_factura_id := (v_registro->>'id_factura')::BIGINT;

        SELECT f.id_socio
          INTO v_socio_id
        FROM public.facturas f
        WHERE f.id_factura = v_factura_id;
    ELSIF TG_TABLE_NAME = 'tarifas' THEN
        v_tarifa_id := (v_registro->>'id_tarifa')::BIGINT;
    END IF;

    CASE TG_OP
        WHEN 'INSERT' THEN
            INSERT INTO public.auditoria (
                tabla, accion, usuario, id_empleado, id_socio, id_factura,
                id_cobro, id_tarifa, datos_despues
            )
            VALUES (
                TG_TABLE_NAME, TG_OP, v_auth_uid::TEXT, v_actor_empleado_id,
                v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id, to_jsonb(NEW)
            );
        WHEN 'UPDATE' THEN
            INSERT INTO public.auditoria (
                tabla, accion, usuario, id_empleado, id_socio, id_factura,
                id_cobro, id_tarifa, datos_antes, datos_despues
            )
            VALUES (
                TG_TABLE_NAME, TG_OP, v_auth_uid::TEXT, v_actor_empleado_id,
                v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id,
                to_jsonb(OLD), to_jsonb(NEW)
            );
        WHEN 'DELETE' THEN
            INSERT INTO public.auditoria (
                tabla, accion, usuario, id_empleado, id_socio, id_factura,
                id_cobro, id_tarifa, datos_antes
            )
            VALUES (
                TG_TABLE_NAME, TG_OP, v_auth_uid::TEXT, v_actor_empleado_id,
                v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id, to_jsonb(OLD)
            );
            RETURN OLD;
    END CASE;

    RETURN NEW;
END;
$$;
SQL);
    }

    public function down(): void
    {
    }
};