<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE SCHEMA IF NOT EXISTS extensions;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION public.epsas_auth_uid()
RETURNS UUID
LANGUAGE plpgsql
STABLE
SET search_path = public
AS $$
DECLARE
    v_uid UUID;
BEGIN
    BEGIN
        EXECUTE 'SELECT auth.uid()' INTO v_uid;
    EXCEPTION
        WHEN invalid_schema_name OR undefined_function THEN
            v_uid := NULL;
    END;

    IF v_uid IS NULL THEN
        BEGIN
            v_uid := NULLIF(current_setting('app.supabase_uid', TRUE), '')::UUID;
        EXCEPTION
            WHEN invalid_text_representation THEN
                v_uid := NULL;
        END;
    END IF;

    RETURN v_uid;
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_current_tenant_id()
RETURNS UUID
LANGUAGE plpgsql
STABLE
SET search_path = public
AS $$
DECLARE
    v_tenant UUID;
BEGIN
    BEGIN
        v_tenant := NULLIF(current_setting('app.current_tenant_id', TRUE), '')::UUID;
    EXCEPTION
        WHEN invalid_text_representation THEN
            v_tenant := NULL;
    END;

    RETURN COALESCE(v_tenant, '00000000-0000-0000-0000-000000000001'::UUID);
END;
$$;

CREATE OR REPLACE FUNCTION public.get_current_tenant()
RETURNS UUID
LANGUAGE sql
STABLE
SET search_path = public
AS $$
    SELECT public.epsas_current_tenant_id();
$$;

CREATE OR REPLACE FUNCTION public.set_current_tenant(tenant_id_param UUID)
RETURNS VOID
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
    IF tenant_id_param IS NULL THEN
        RAISE EXCEPTION 'tenant_id no puede ser nulo';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM public.tenants WHERE id = tenant_id_param AND is_active = TRUE) THEN
        RAISE EXCEPTION 'tenant_id no existe o esta inactivo';
    END IF;

    PERFORM set_config('app.current_tenant_id', tenant_id_param::TEXT, FALSE);
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_actor_user_id()
RETURNS BIGINT
LANGUAGE plpgsql
STABLE
SET search_path = public
AS $$
DECLARE
    v_actor TEXT;
BEGIN
    v_actor := NULLIF(current_setting('app.user_id', TRUE), '');

    IF v_actor ~ '^[0-9]+$' THEN
        RETURN v_actor::BIGINT;
    END IF;

    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_local_user_id_to_auth_uuid(p_user_id BIGINT)
RETURNS UUID
LANGUAGE sql
STABLE
SET search_path = public
AS $$
    SELECT NULL::UUID;
$$;

CREATE TABLE IF NOT EXISTS public.tenants (
    id UUID PRIMARY KEY DEFAULT extensions.gen_random_uuid(),
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    config JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
);

INSERT INTO public.tenants (id, name, slug, config, is_active, created_at, updated_at)
VALUES ('00000000-0000-0000-0000-000000000001', 'EPSAS El Portillo', 'epsas-el-portillo', '{}'::jsonb, TRUE, now(), now())
ON CONFLICT (id) DO UPDATE
SET name = EXCLUDED.name,
    slug = EXCLUDED.slug,
    is_active = EXCLUDED.is_active,
    updated_at = now();

DO $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN
        SELECT DISTINCT event_object_table AS table_name
        FROM information_schema.triggers
        WHERE trigger_schema = 'public'
          AND trigger_name = 'trg_epsas_lifecycle'
    LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_epsas_lifecycle ON public.%I', rec.table_name);
    END LOOP;
END;
$$;

DO $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN
        SELECT c.table_name, c.column_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema
         AND t.table_name = c.table_name
        WHERE c.table_schema = 'public'
          AND t.table_type = 'BASE TABLE'
          AND c.column_name IN ('created_by', 'updated_by', 'owner_id')
          AND c.udt_name <> 'uuid'
    LOOP
        EXECUTE format('ALTER TABLE public.%I ALTER COLUMN %I DROP DEFAULT', rec.table_name, rec.column_name);
        EXECUTE format(
            'ALTER TABLE public.%I ALTER COLUMN %I TYPE UUID USING NULL::UUID',
            rec.table_name,
            rec.column_name
        );
    END LOOP;
END;
$$;

DO $$
DECLARE
    rec RECORD;
    constraint_name TEXT;
BEGIN
    IF to_regclass('auth.users') IS NULL THEN
        RETURN;
    END IF;

    IF to_regclass('public.empleados') IS NOT NULL THEN
        UPDATE public.empleados e
           SET user_id = NULL
         WHERE e.user_id IS NOT NULL
           AND NOT EXISTS (
               SELECT 1
               FROM auth.users au
               WHERE au.id = e.user_id
           );

        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint
            WHERE conname = 'empleados_user_id_auth_users_fk'
        ) THEN
            ALTER TABLE public.empleados
                ADD CONSTRAINT empleados_user_id_auth_users_fk
                FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;
    END IF;

    FOR rec IN
        SELECT c.table_name, c.column_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema
         AND t.table_name = c.table_name
        WHERE c.table_schema = 'public'
          AND t.table_type = 'BASE TABLE'
          AND c.column_name IN ('created_by', 'updated_by', 'owner_id')
          AND c.udt_name = 'uuid'
    LOOP
        constraint_name := format('fk_%s_%s_auth_users', rec.table_name, rec.column_name);

        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint
            WHERE conname = constraint_name
        ) THEN
            EXECUTE format(
                'ALTER TABLE public.%I ADD CONSTRAINT %I FOREIGN KEY (%I) REFERENCES auth.users(id) ON DELETE SET NULL',
                rec.table_name,
                constraint_name,
                rec.column_name
            );
        END IF;
    END LOOP;
END;
$$;

DO $$
DECLARE
    rec RECORD;
    constraint_name TEXT;
BEGIN
    FOR rec IN
        SELECT c.table_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema
         AND t.table_name = c.table_name
        WHERE c.table_schema = 'public'
          AND t.table_type = 'BASE TABLE'
          AND c.column_name = 'tenant_id'
          AND c.table_name <> 'tenants'
    LOOP
        EXECUTE format(
            'UPDATE public.%I SET tenant_id = ''00000000-0000-0000-0000-000000000001'' WHERE tenant_id IS NULL OR NOT EXISTS (SELECT 1 FROM public.tenants t WHERE t.id = public.%I.tenant_id)',
            rec.table_name,
            rec.table_name
        );

        constraint_name := format('fk_%s_tenant', rec.table_name);

        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint
            WHERE conname = constraint_name
        ) THEN
            EXECUTE format(
                'ALTER TABLE public.%I ADD CONSTRAINT %I FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE RESTRICT',
                rec.table_name,
                constraint_name
            );
        END IF;
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_touch_lifecycle_columns()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_actor UUID;
BEGIN
    v_actor := public.epsas_auth_uid();

    IF TG_OP = 'INSERT' THEN
        NEW.created_at := COALESCE(NEW.created_at, now());
        NEW.updated_at := COALESCE(NEW.updated_at, NEW.created_at, now());
        NEW.created_by := COALESCE(NEW.created_by, v_actor);
        NEW.updated_by := COALESCE(NEW.updated_by, NEW.created_by, v_actor);
        NEW.tenant_id := COALESCE(NEW.tenant_id, public.epsas_current_tenant_id());
        NEW.is_deleted := COALESCE(NEW.is_deleted, FALSE);
        NEW.is_active := COALESCE(NEW.is_active, TRUE);
        NEW.visibility := COALESCE(NULLIF(NEW.visibility, ''), 'private');
        NEW.owner_id := COALESCE(NEW.owner_id, v_actor);
        NEW.metadata := COALESCE(NEW.metadata, '{}'::jsonb);
        NEW.version := GREATEST(COALESCE(NEW.version, 1), 1);

        IF NEW.is_deleted AND NEW.deleted_at IS NULL THEN
            NEW.deleted_at := now();
        END IF;
    ELSE
        NEW.updated_at := now();
        NEW.updated_by := COALESCE(v_actor, NEW.updated_by);
        NEW.version := GREATEST(COALESCE(OLD.version, 0) + 1, COALESCE(NEW.version, 1));

        IF NEW.is_deleted AND NOT COALESCE(OLD.is_deleted, FALSE) AND NEW.deleted_at IS NULL THEN
            NEW.deleted_at := now();
            NEW.is_active := FALSE;
        END IF;
    END IF;

    RETURN NEW;
END;
$$;

DO $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN
        SELECT c.table_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema
         AND t.table_name = c.table_name
        WHERE c.table_schema = 'public'
          AND t.table_type = 'BASE TABLE'
          AND c.column_name IN (
              'created_at', 'updated_at', 'created_by', 'updated_by',
              'tenant_id', 'is_deleted', 'deleted_at', 'is_active',
              'visibility', 'owner_id', 'metadata', 'version'
          )
        GROUP BY c.table_name
        HAVING COUNT(DISTINCT c.column_name) = 12
    LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_epsas_lifecycle ON public.%I', rec.table_name);
        EXECUTE format(
            'CREATE TRIGGER trg_epsas_lifecycle BEFORE INSERT OR UPDATE ON public.%I FOR EACH ROW EXECUTE FUNCTION public.epsas_touch_lifecycle_columns()',
            rec.table_name
        );
    END LOOP;
END;
$$;

DO $$
DECLARE
    proc_name TEXT;
    proc_oid REGPROCEDURE;
    function_names TEXT[] := ARRAY[
        'public.guardar_tarifa_factura()',
        'public.marcar_facturas_vencidas()',
        'public.validar_pago_factura()',
        'public.epsas_auth_uid()',
        'public.epsas_actor_user_id()',
        'public.epsas_current_tenant_id()',
        'public.get_current_tenant()',
        'public.set_current_tenant(uuid)',
        'public.epsas_local_user_id_to_auth_uuid(bigint)',
        'public.epsas_touch_lifecycle_columns()',
        'public.epsas_refresh_search_vector()',
        'public.epsas_refresh_factura_balance(bigint)',
        'public.epsas_refresh_socio_balance(bigint)',
        'public.epsas_after_cobro_balance()',
        'public.epsas_after_factura_balance()',
        'public.actualizar_estado_factura()',
        'public.auditoria_trigger()',
        'public.get_mi_empleado_id()',
        'public.get_mi_rol()',
        'public.registrar_historial_cobro()',
        'public.tiene_rol(text[])'
    ];
BEGIN
    FOREACH proc_name IN ARRAY function_names LOOP
        proc_oid := to_regprocedure(proc_name);

        IF proc_oid IS NOT NULL THEN
            EXECUTE format('ALTER FUNCTION %s SET search_path = public, extensions', proc_oid);
        END IF;
    END LOOP;
END;
$$;

DO $$
DECLARE
    proc_name TEXT;
    proc_oid REGPROCEDURE;
    role_name TEXT;
    function_names TEXT[] := ARRAY[
        'public.actualizar_estado_factura()',
        'public.auditoria_trigger()',
        'public.get_mi_empleado_id()',
        'public.get_mi_rol()',
        'public.registrar_historial_cobro()',
        'public.tiene_rol(text[])',
        'public.set_current_tenant(uuid)'
    ];
BEGIN
    FOREACH proc_name IN ARRAY function_names LOOP
        proc_oid := to_regprocedure(proc_name);

        IF proc_oid IS NOT NULL THEN
            EXECUTE format('REVOKE EXECUTE ON FUNCTION %s FROM PUBLIC', proc_oid);

            FOREACH role_name IN ARRAY ARRAY['anon', 'authenticated'] LOOP
                IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = role_name) THEN
                    EXECUTE format('REVOKE EXECUTE ON FUNCTION %s FROM %I', proc_oid, role_name);
                END IF;
            END LOOP;
        END IF;
    END LOOP;
END;
$$;

CREATE INDEX IF NOT EXISTS idx_facturas_socio_periodo_estado ON public.facturas (id_socio, id_periodo, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_facturas_tenant_estado_active ON public.facturas (tenant_id, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_facturas_fecha_estado_active ON public.facturas (fecha_emision DESC, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_facturas_socio_periodo_active ON public.facturas (id_socio, id_periodo) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_facturas_fecha_emision_active ON public.facturas (fecha_emision DESC) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_cobros_fecha_estado_active ON public.cobros (fecha_cobro DESC, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_cobros_factura_estado_active ON public.cobros (id_factura, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_cobros_cierre_caja_active ON public.cobros (id_cierre_caja) WHERE id_cierre_caja IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_socios_tenant_estado_numero ON public.socios (tenant_id, estado, numero_socio) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_socios_tenant_estado_active ON public.socios (tenant_id, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_socios_numero_socio_active ON public.socios (numero_socio) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_socios_deuda_active ON public.socios (deuda_total DESC, numero_socio) WHERE is_deleted = FALSE AND deuda_total > 0;
CREATE INDEX IF NOT EXISTS idx_lecturas_medidor_fecha_active ON public.lecturas (id_medidor, fecha_lectura DESC) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_lecturas_empleado_fecha_active ON public.lecturas (id_empleado, fecha_lectura DESC) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_medidores_socio_active ON public.medidores (id_socio) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_medidores_serie_active ON public.medidores (numero_serie) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_ordenes_pago_socio_estado ON public.ordenes_pago (id_socio, estado) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_ordenes_pago_codigo_active ON public.ordenes_pago (codigo) WHERE estado IN ('pendiente', 'en_revision') AND is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_ordenes_pago_fecha_vencimiento_active ON public.ordenes_pago (fecha_vencimiento) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_auditoria_fecha ON public.auditoria (fecha DESC);
CREATE INDEX IF NOT EXISTS idx_auditoria_tabla_fecha ON public.auditoria (tabla, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_auditoria_tenant_fecha ON public.auditoria (tenant_id, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_historial_pagos_factura ON public.historial_pagos (id_factura);
CREATE INDEX IF NOT EXISTS idx_historial_pagos_tenant_fecha ON public.historial_pagos (tenant_id, fecha_evento DESC);
CREATE INDEX IF NOT EXISTS idx_notificaciones_socio_leido ON public.notificaciones (id_socio, leido) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_notificaciones_tenant_enviado_fecha ON public.notificaciones (tenant_id, enviado, fecha_envio DESC);
CREATE INDEX IF NOT EXISTS idx_notificaciones_tenant_envio ON public.notificaciones (tenant_id, fecha_envio DESC);
DO $$
BEGIN
    IF to_regclass('public.jobs') IS NOT NULL THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_jobs_queue_available ON public.jobs (queue, available_at) WHERE reserved_at IS NULL';
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_jobs_reserved_at ON public.jobs (reserved_at) WHERE reserved_at IS NOT NULL';
    END IF;

    IF to_regclass('public.cache') IS NOT NULL THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_cache_expiration ON public.cache (expiration)';
    END IF;

    IF to_regclass('public.sessions') IS NOT NULL THEN
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_sessions_user_last_activity ON public.sessions (user_id, last_activity DESC) WHERE user_id IS NOT NULL';
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON public.sessions (last_activity DESC)';
    END IF;
END;
$$;

CREATE TABLE IF NOT EXISTS public.tenant_config (
    id UUID PRIMARY KEY DEFAULT extensions.gen_random_uuid(),
    tenant_id UUID NOT NULL DEFAULT public.epsas_current_tenant_id(),
    config_key TEXT NOT NULL,
    config_value JSONB NOT NULL DEFAULT '{}'::jsonb,
    description TEXT,
    is_encrypted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    created_by UUID,
    updated_by UUID,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    deleted_at TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    visibility VARCHAR(32) NOT NULL DEFAULT 'private',
    owner_id UUID,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    version INTEGER NOT NULL DEFAULT 1,
    UNIQUE (tenant_id, config_key)
);

CREATE TABLE IF NOT EXISTS public.access_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID,
    tenant_id UUID NOT NULL DEFAULT public.epsas_current_tenant_id(),
    action TEXT NOT NULL,
    resource TEXT NOT NULL,
    resource_id BIGINT,
    ip_address INET,
    user_agent TEXT,
    details JSONB NOT NULL DEFAULT '{}'::jsonb,
    success BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    created_by UUID,
    updated_by UUID,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    deleted_at TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    visibility VARCHAR(32) NOT NULL DEFAULT 'private',
    owner_id UUID,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    version INTEGER NOT NULL DEFAULT 1
);

DO $$
BEGIN
    IF to_regclass('auth.users') IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tenant_config_created_by_auth_users_fk') THEN
            ALTER TABLE public.tenant_config
                ADD CONSTRAINT tenant_config_created_by_auth_users_fk
                FOREIGN KEY (created_by) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tenant_config_updated_by_auth_users_fk') THEN
            ALTER TABLE public.tenant_config
                ADD CONSTRAINT tenant_config_updated_by_auth_users_fk
                FOREIGN KEY (updated_by) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tenant_config_owner_id_auth_users_fk') THEN
            ALTER TABLE public.tenant_config
                ADD CONSTRAINT tenant_config_owner_id_auth_users_fk
                FOREIGN KEY (owner_id) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'access_logs_user_id_auth_users_fk') THEN
            ALTER TABLE public.access_logs
                ADD CONSTRAINT access_logs_user_id_auth_users_fk
                FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'access_logs_created_by_auth_users_fk') THEN
            ALTER TABLE public.access_logs
                ADD CONSTRAINT access_logs_created_by_auth_users_fk
                FOREIGN KEY (created_by) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'access_logs_updated_by_auth_users_fk') THEN
            ALTER TABLE public.access_logs
                ADD CONSTRAINT access_logs_updated_by_auth_users_fk
                FOREIGN KEY (updated_by) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;

        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'access_logs_owner_id_auth_users_fk') THEN
            ALTER TABLE public.access_logs
                ADD CONSTRAINT access_logs_owner_id_auth_users_fk
                FOREIGN KEY (owner_id) REFERENCES auth.users(id) ON DELETE SET NULL;
        END IF;
    END IF;
END;
$$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'tenant_config_tenant_fk') THEN
        ALTER TABLE public.tenant_config
            ADD CONSTRAINT tenant_config_tenant_fk
            FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE RESTRICT;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'access_logs_tenant_fk') THEN
        ALTER TABLE public.access_logs
            ADD CONSTRAINT access_logs_tenant_fk
            FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE RESTRICT;
    END IF;
END;
$$;

ALTER TABLE public.tenant_config ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.access_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tenants ENABLE ROW LEVEL SECURITY;

DROP TRIGGER IF EXISTS trg_epsas_lifecycle ON public.tenant_config;
CREATE TRIGGER trg_epsas_lifecycle
BEFORE INSERT OR UPDATE ON public.tenant_config
FOR EACH ROW
EXECUTE FUNCTION public.epsas_touch_lifecycle_columns();

DROP TRIGGER IF EXISTS trg_epsas_lifecycle ON public.access_logs;
CREATE TRIGGER trg_epsas_lifecycle
BEFORE INSERT OR UPDATE ON public.access_logs
FOR EACH ROW
EXECUTE FUNCTION public.epsas_touch_lifecycle_columns();

CREATE INDEX IF NOT EXISTS idx_tenant_config_tenant_key ON public.tenant_config (tenant_id, config_key) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_access_logs_tenant_created ON public.access_logs (tenant_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_access_logs_user_created ON public.access_logs (user_id, created_at DESC) WHERE user_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_access_logs_resource_created ON public.access_logs (resource, resource_id, created_at DESC);

DO $$
DECLARE
    rec RECORD;
    policy_name TEXT;
BEGIN
    FOR rec IN
        SELECT c.table_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema
         AND t.table_name = c.table_name
        WHERE c.table_schema = 'public'
          AND t.table_type = 'BASE TABLE'
          AND c.column_name = 'tenant_id'
          AND c.table_name <> 'tenants'
    LOOP
        policy_name := format('tenant_isolation_%s', rec.table_name);

        EXECUTE format('DROP POLICY IF EXISTS %I ON public.%I', policy_name, rec.table_name);
        EXECUTE format(
            'CREATE POLICY %I ON public.%I AS RESTRICTIVE FOR ALL TO PUBLIC USING (tenant_id = public.epsas_current_tenant_id()) WITH CHECK (tenant_id = public.epsas_current_tenant_id())',
            policy_name,
            rec.table_name
        );
    END LOOP;
END;
$$;

DROP POLICY IF EXISTS tenants_no_public_access ON public.tenants;
CREATE POLICY tenants_no_public_access ON public.tenants
AS RESTRICTIVE
FOR ALL
TO PUBLIC
USING (FALSE)
WITH CHECK (FALSE);

DROP POLICY IF EXISTS prevent_hard_delete_facturas ON public.facturas;
CREATE POLICY prevent_hard_delete_facturas ON public.facturas
AS RESTRICTIVE
FOR DELETE
TO PUBLIC
USING (FALSE);

INSERT INTO public.tenant_config (tenant_id, config_key, config_value, description)
VALUES
    ('00000000-0000-0000-0000-000000000001', 'facturacion.config', '{"dias_vencimiento": 30, "recargo_mora_porcentaje": 5, "consumo_minimo_gratuito": 5}'::jsonb, 'Parametros base de facturacion'),
    ('00000000-0000-0000-0000-000000000001', 'pagos.config', '{"metodos_aceptados": ["efectivo", "qr", "transferencia"], "requiere_conciliacion": true}'::jsonb, 'Parametros base de pagos')
ON CONFLICT (tenant_id, config_key) DO NOTHING;

DROP MATERIALIZED VIEW IF EXISTS public.mv_resumen_facturacion;
CREATE MATERIALIZED VIEW public.mv_resumen_facturacion AS
SELECT
    p.id_periodo,
    p.nombre AS periodo_nombre,
    p.fecha_inicio,
    p.fecha_fin,
    COUNT(DISTINCT f.id_socio) AS total_socios_facturados,
    COUNT(f.id_factura) AS total_facturas,
    COALESCE(SUM(f.total), 0)::NUMERIC(14, 2) AS monto_total_facturado,
    COALESCE(SUM(f.pagado_total), 0)::NUMERIC(14, 2) AS monto_cobrado,
    COALESCE(SUM(f.saldo_pendiente) FILTER (WHERE f.estado IN ('pendiente', 'parcial', 'vencida')), 0)::NUMERIC(14, 2) AS monto_pendiente,
    COALESCE(AVG(f.total), 0)::NUMERIC(14, 2) AS promedio_factura,
    COALESCE(AVG(f.consumo_m3), 0)::NUMERIC(14, 2) AS promedio_consumo
FROM public.periodos_facturacion p
LEFT JOIN public.facturas f
       ON p.id_periodo = f.id_periodo
      AND COALESCE(f.is_deleted, FALSE) = FALSE
WHERE COALESCE(p.is_deleted, FALSE) = FALSE
GROUP BY p.id_periodo, p.nombre, p.fecha_inicio, p.fecha_fin;

CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_resumen_facturacion_periodo ON public.mv_resumen_facturacion (id_periodo);

DROP MATERIALIZED VIEW IF EXISTS public.mv_cobranza_diaria;
CREATE MATERIALIZED VIEW public.mv_cobranza_diaria AS
SELECT
    COALESCE(c.tenant_id, public.epsas_current_tenant_id()) AS tenant_id,
    c.fecha_cobro::DATE AS fecha,
    COALESCE(c.origen_pago, 'caja') AS origen_pago,
    c.id_metodo_pago,
    COALESCE(mp.nombre, 'Sin metodo') AS metodo_pago,
    COUNT(c.id_cobro) AS total_cobros,
    COALESCE(SUM(c.monto_pagado), 0)::NUMERIC(14, 2) AS monto_recaudado,
    COUNT(c.id_cobro) FILTER (WHERE c.estado IN ('confirmado', 'completado')) AS cobros_confirmados,
    COUNT(c.id_cobro) FILTER (WHERE c.estado = 'parcial') AS cobros_parciales
FROM public.cobros c
LEFT JOIN public.metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
WHERE COALESCE(c.is_deleted, FALSE) = FALSE
  AND c.estado <> 'anulado'
GROUP BY COALESCE(c.tenant_id, public.epsas_current_tenant_id()), c.fecha_cobro::DATE, COALESCE(c.origen_pago, 'caja'), c.id_metodo_pago, COALESCE(mp.nombre, 'Sin metodo');

CREATE INDEX IF NOT EXISTS idx_mv_cobranza_diaria_fecha ON public.mv_cobranza_diaria (tenant_id, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_mv_cobranza_diaria_origen ON public.mv_cobranza_diaria (tenant_id, origen_pago, fecha DESC);

CREATE OR REPLACE VIEW public.vw_table_sizes
WITH (security_invoker = true)
AS
SELECT
    n.nspname AS schemaname,
    c.relname AS tablename,
    pg_size_pretty(pg_total_relation_size(c.oid)) AS total_size,
    pg_size_pretty(pg_relation_size(c.oid)) AS table_size,
    pg_size_pretty(pg_total_relation_size(c.oid) - pg_relation_size(c.oid)) AS index_size,
    (
        SELECT COUNT(*)
        FROM information_schema.columns cols
        WHERE cols.table_schema = n.nspname
          AND cols.table_name = c.relname
    ) AS column_count
FROM pg_class c
JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE n.nspname = 'public'
  AND c.relkind IN ('r', 'm')
ORDER BY pg_total_relation_size(c.oid) DESC;

CREATE OR REPLACE VIEW public.vw_performance_alerts
WITH (security_invoker = true)
AS
SELECT
    schemaname,
    relname AS tablename,
    seq_scan,
    seq_tup_read,
    idx_scan,
    (seq_scan::NUMERIC / NULLIF(seq_scan + idx_scan, 0)) * 100 AS seq_scan_percentage,
    n_dead_tup,
    last_autovacuum,
    CASE
        WHEN seq_scan > 0 AND idx_scan = 0 THEN 'ALERTA: tabla sin uso de indices'
        WHEN n_dead_tup > 10000 THEN 'ALERTA: demasiadas filas muertas'
        WHEN ((seq_scan::NUMERIC / NULLIF(seq_scan + idx_scan, 0)) * 100) > 50 THEN 'ADVERTENCIA: alto uso de lecturas secuenciales'
        ELSE 'OK'
    END AS alert_status
FROM pg_stat_user_tables
WHERE schemaname = 'public'
  AND (seq_scan + idx_scan) > 0
ORDER BY seq_scan_percentage DESC NULLS LAST;

DROP TABLE IF EXISTS public.failed_jobs CASCADE;
DROP TABLE IF EXISTS public.job_batches CASCADE;
DROP TABLE IF EXISTS public.jobs CASCADE;
DROP TABLE IF EXISTS public.cache_locks CASCADE;
DROP TABLE IF EXISTS public.cache CASCADE;
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS public.vw_performance_alerts;
DROP VIEW IF EXISTS public.vw_table_sizes;
DROP MATERIALIZED VIEW IF EXISTS public.mv_cobranza_diaria;
DROP MATERIALIZED VIEW IF EXISTS public.mv_resumen_facturacion;
DROP TABLE IF EXISTS public.access_logs;
DROP TABLE IF EXISTS public.tenant_config;
DROP FUNCTION IF EXISTS public.epsas_local_user_id_to_auth_uuid(BIGINT);
DROP FUNCTION IF EXISTS public.set_current_tenant(UUID);
DROP FUNCTION IF EXISTS public.get_current_tenant();
DROP FUNCTION IF EXISTS public.epsas_current_tenant_id();
SQL);
    }
};
