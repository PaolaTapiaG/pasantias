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
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION public.epsas_auth_uid()
RETURNS UUID
LANGUAGE plpgsql
STABLE
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

CREATE OR REPLACE FUNCTION public.epsas_actor_user_id()
RETURNS BIGINT
LANGUAGE plpgsql
STABLE
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

CREATE TABLE IF NOT EXISTS public.organizations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb
);

INSERT INTO public.organizations (id, code, name, status, is_active, created_at, updated_at)
VALUES ('00000000-0000-0000-0000-000000000001', 'epsas', 'EPSAS El Portillo', 'active', TRUE, now(), now())
ON CONFLICT (id) DO UPDATE
SET code = EXCLUDED.code,
    name = EXCLUDED.name,
    status = EXCLUDED.status,
    is_active = EXCLUDED.is_active,
    updated_at = now();

CREATE TABLE IF NOT EXISTS public.historial_pagos (
    id_historial BIGSERIAL PRIMARY KEY,
    fecha_evento TIMESTAMP WITH TIME ZONE DEFAULT now(),
    tipo_evento VARCHAR(120) NOT NULL,
    descripcion TEXT,
    monto NUMERIC(12, 2) NOT NULL DEFAULT 0,
    id_socio BIGINT,
    id_factura BIGINT,
    id_cobro BIGINT,
    id_empleado BIGINT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
);

ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS fecha_evento TIMESTAMP WITH TIME ZONE DEFAULT now();
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS tipo_evento VARCHAR(120);
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS descripcion TEXT;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS monto NUMERIC(12, 2) NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS id_socio BIGINT;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS id_factura BIGINT;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS id_cobro BIGINT;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS id_empleado BIGINT;
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now();
ALTER TABLE IF EXISTS public.historial_pagos ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now();

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'historial_pagos_id_socio_fk') THEN
        ALTER TABLE public.historial_pagos
            ADD CONSTRAINT historial_pagos_id_socio_fk
            FOREIGN KEY (id_socio) REFERENCES public.socios(id_socio) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'historial_pagos_id_factura_fk') THEN
        ALTER TABLE public.historial_pagos
            ADD CONSTRAINT historial_pagos_id_factura_fk
            FOREIGN KEY (id_factura) REFERENCES public.facturas(id_factura) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'historial_pagos_id_cobro_fk') THEN
        ALTER TABLE public.historial_pagos
            ADD CONSTRAINT historial_pagos_id_cobro_fk
            FOREIGN KEY (id_cobro) REFERENCES public.cobros(id_cobro) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'historial_pagos_id_empleado_fk') THEN
        ALTER TABLE public.historial_pagos
            ADD CONSTRAINT historial_pagos_id_empleado_fk
            FOREIGN KEY (id_empleado) REFERENCES public.empleados(id_empleado) ON DELETE SET NULL;
    END IF;
END;
$$;

DO $$
DECLARE
    table_name TEXT;
    tables TEXT[] := ARRAY[
        'organizations',
        'users',
        'user_roles',
        'user_permissions',
        'role_user',
        'permission_role',
        'personas',
        'roles',
        'sectores',
        'tarifas',
        'empleados',
        'socios',
        'medidores',
        'periodos_facturacion',
        'lecturas',
        'facturas',
        'metodos_pago',
        'cobros',
        'historial_pagos',
        'notificaciones',
        'auditoria',
        'gastos',
        'medidor_anomalias',
        'ordenes_tecnicas',
        'operaciones_sistema',
        'incidencias_tecnicas',
        'reportes_tecnicos',
        'ordenes_pago',
        'orden_pago_detalles',
        'ingresos_administrativos',
        'cierres_caja',
        'comunicados',
        'media_retention_events',
        'bank_payment_events',
        'sms_messages',
        'system_settings'
    ];
BEGIN
    FOREACH table_name IN ARRAY tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS tenant_id UUID NOT NULL DEFAULT ''00000000-0000-0000-0000-000000000001''', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITH TIME ZONE DEFAULT now()', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS created_by BIGINT', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS updated_by BIGINT', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN NOT NULL DEFAULT FALSE', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP WITH TIME ZONE', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS visibility VARCHAR(32) NOT NULL DEFAULT ''private''', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS owner_id BIGINT', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS metadata JSONB NOT NULL DEFAULT ''{}''::jsonb', table_name);
            EXECUTE format('ALTER TABLE public.%I ADD COLUMN IF NOT EXISTS version INTEGER NOT NULL DEFAULT 1', table_name);

            EXECUTE format('CREATE INDEX IF NOT EXISTS idx_%s_tenant_active ON public.%I (tenant_id, is_deleted, is_active)', table_name, table_name);
            EXECUTE format('CREATE INDEX IF NOT EXISTS idx_%s_owner ON public.%I (owner_id) WHERE owner_id IS NOT NULL', table_name, table_name);
        END IF;
    END LOOP;
END;
$$;

ALTER TABLE IF EXISTS public.bank_payment_events ADD COLUMN IF NOT EXISTS last_error TEXT;
ALTER TABLE IF EXISTS public.bank_payment_events ADD COLUMN IF NOT EXISTS retry_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.media_retention_events ADD COLUMN IF NOT EXISTS last_error TEXT;
ALTER TABLE IF EXISTS public.media_retention_events ADD COLUMN IF NOT EXISTS retry_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.orden_pago_detalles
    ALTER COLUMN metadata TYPE JSONB USING COALESCE(metadata::jsonb, '{}'::jsonb),
    ALTER COLUMN metadata SET DEFAULT '{}'::jsonb,
    ALTER COLUMN metadata SET NOT NULL;

CREATE OR REPLACE FUNCTION public.epsas_touch_lifecycle_columns()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_actor BIGINT;
BEGIN
    v_actor := public.epsas_actor_user_id();

    IF TG_OP = 'INSERT' THEN
        NEW.created_at := COALESCE(NEW.created_at, now());
        NEW.updated_at := COALESCE(NEW.updated_at, NEW.created_at, now());
        NEW.created_by := COALESCE(NEW.created_by, v_actor);
        NEW.updated_by := COALESCE(NEW.updated_by, NEW.created_by, v_actor);
        NEW.tenant_id := COALESCE(NEW.tenant_id, '00000000-0000-0000-0000-000000000001'::UUID);
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
    table_name TEXT;
    tables TEXT[] := ARRAY[
        'organizations',
        'users',
        'user_roles',
        'user_permissions',
        'role_user',
        'permission_role',
        'personas',
        'roles',
        'sectores',
        'tarifas',
        'empleados',
        'socios',
        'medidores',
        'periodos_facturacion',
        'lecturas',
        'facturas',
        'metodos_pago',
        'cobros',
        'historial_pagos',
        'notificaciones',
        'auditoria',
        'gastos',
        'medidor_anomalias',
        'ordenes_tecnicas',
        'operaciones_sistema',
        'incidencias_tecnicas',
        'reportes_tecnicos',
        'ordenes_pago',
        'orden_pago_detalles',
        'ingresos_administrativos',
        'cierres_caja',
        'comunicados',
        'media_retention_events',
        'bank_payment_events',
        'sms_messages',
        'system_settings'
    ];
BEGIN
    FOREACH table_name IN ARRAY tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('DROP TRIGGER IF EXISTS trg_epsas_lifecycle ON public.%I', table_name);
            EXECUTE format(
                'CREATE TRIGGER trg_epsas_lifecycle BEFORE INSERT OR UPDATE ON public.%I FOR EACH ROW EXECUTE FUNCTION public.epsas_touch_lifecycle_columns()',
                table_name
            );
        END IF;
    END LOOP;
END;
$$;

ALTER TABLE IF EXISTS public.personas ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.socios ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.medidores ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.facturas ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.gastos ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.ordenes_pago ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.comunicados ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.incidencias_tecnicas ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
ALTER TABLE IF EXISTS public.ordenes_tecnicas ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;

CREATE OR REPLACE FUNCTION public.epsas_refresh_search_vector()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
BEGIN
    IF TG_TABLE_NAME = 'personas' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.nombres, NEW.apellidos, NEW.cedula_identidad, NEW.telefono, NEW.email));
    ELSIF TG_TABLE_NAME = 'socios' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.numero_socio, NEW.direccion, NEW.estado));
    ELSIF TG_TABLE_NAME = 'medidores' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.numero_serie, NEW.marca, NEW.modelo, NEW.estado));
    ELSIF TG_TABLE_NAME = 'facturas' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.numero_factura, NEW.estado));
    ELSIF TG_TABLE_NAME = 'gastos' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.concepto, NEW.categoria, NEW.descripcion));
    ELSIF TG_TABLE_NAME = 'ordenes_pago' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.codigo, NEW.estado, NEW.metodo, NEW.comprobante_referencia, NEW.entidad_financiera));
    ELSIF TG_TABLE_NAME = 'comunicados' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.titulo, NEW.contenido, NEW.estado));
    ELSIF TG_TABLE_NAME = 'incidencias_tecnicas' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.tipo, NEW.prioridad, NEW.estado, NEW.zona, NEW.descripcion));
    ELSIF TG_TABLE_NAME = 'ordenes_tecnicas' THEN
        NEW.search_vector := to_tsvector('simple', concat_ws(' ', NEW.tipo, NEW.prioridad, NEW.estado, NEW.zona, NEW.referencia, NEW.descripcion));
    END IF;

    RETURN NEW;
END;
$$;

DO $$
DECLARE
    table_name TEXT;
    tables TEXT[] := ARRAY[
        'personas',
        'socios',
        'medidores',
        'facturas',
        'gastos',
        'ordenes_pago',
        'comunicados',
        'incidencias_tecnicas',
        'ordenes_tecnicas'
    ];
BEGIN
    FOREACH table_name IN ARRAY tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.%I', table_name);
            EXECUTE format(
                'CREATE TRIGGER trg_epsas_search_vector BEFORE INSERT OR UPDATE ON public.%I FOR EACH ROW EXECUTE FUNCTION public.epsas_refresh_search_vector()',
                table_name
            );
        END IF;
    END LOOP;
END;
$$;

UPDATE public.personas SET search_vector = to_tsvector('simple', concat_ws(' ', nombres, apellidos, cedula_identidad, telefono, email)) WHERE search_vector IS NULL;
UPDATE public.socios SET search_vector = to_tsvector('simple', concat_ws(' ', numero_socio, direccion, estado)) WHERE search_vector IS NULL;
UPDATE public.medidores SET search_vector = to_tsvector('simple', concat_ws(' ', numero_serie, marca, modelo, estado)) WHERE search_vector IS NULL;
UPDATE public.facturas SET search_vector = to_tsvector('simple', concat_ws(' ', numero_factura, estado)) WHERE search_vector IS NULL;
UPDATE public.gastos SET search_vector = to_tsvector('simple', concat_ws(' ', concepto, categoria, descripcion)) WHERE search_vector IS NULL;
UPDATE public.ordenes_pago SET search_vector = to_tsvector('simple', concat_ws(' ', codigo, estado, metodo, comprobante_referencia, entidad_financiera)) WHERE search_vector IS NULL;
UPDATE public.comunicados SET search_vector = to_tsvector('simple', concat_ws(' ', titulo, contenido, estado)) WHERE search_vector IS NULL;
UPDATE public.incidencias_tecnicas SET search_vector = to_tsvector('simple', concat_ws(' ', tipo, prioridad, estado, zona, descripcion)) WHERE search_vector IS NULL;
UPDATE public.ordenes_tecnicas SET search_vector = to_tsvector('simple', concat_ws(' ', tipo, prioridad, estado, zona, referencia, descripcion)) WHERE search_vector IS NULL;

CREATE INDEX IF NOT EXISTS idx_personas_search_vector ON public.personas USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_socios_search_vector ON public.socios USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_medidores_search_vector ON public.medidores USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_facturas_search_vector ON public.facturas USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_gastos_search_vector ON public.gastos USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_ordenes_pago_search_vector ON public.ordenes_pago USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_comunicados_search_vector ON public.comunicados USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_incidencias_tecnicas_search_vector ON public.incidencias_tecnicas USING GIN (search_vector);
CREATE INDEX IF NOT EXISTS idx_ordenes_tecnicas_search_vector ON public.ordenes_tecnicas USING GIN (search_vector);

ALTER TABLE IF EXISTS public.facturas ADD COLUMN IF NOT EXISTS pagado_total NUMERIC(12, 2) NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.facturas ADD COLUMN IF NOT EXISTS saldo_pendiente NUMERIC(12, 2) NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.socios ADD COLUMN IF NOT EXISTS deuda_total NUMERIC(12, 2) NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.socios ADD COLUMN IF NOT EXISTS facturas_pendientes_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE IF EXISTS public.socios ADD COLUMN IF NOT EXISTS ultima_fecha_pago DATE;

CREATE OR REPLACE FUNCTION public.epsas_refresh_factura_balance(p_id_factura BIGINT)
RETURNS VOID
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_paid NUMERIC(12, 2);
BEGIN
    IF p_id_factura IS NULL THEN
        RETURN;
    END IF;

    SELECT COALESCE(SUM(c.monto_pagado), 0)::NUMERIC(12, 2)
      INTO v_paid
    FROM public.cobros c
    WHERE c.id_factura = p_id_factura
      AND c.estado <> 'anulado'
      AND COALESCE(c.is_deleted, FALSE) = FALSE;

    UPDATE public.facturas f
       SET pagado_total = v_paid,
           saldo_pendiente = GREATEST((COALESCE(f.total, 0) - v_paid), 0)::NUMERIC(12, 2),
           updated_at = now()
     WHERE f.id_factura = p_id_factura;
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_refresh_socio_balance(p_id_socio BIGINT)
RETURNS VOID
LANGUAGE plpgsql
SET search_path = public
AS $$
BEGIN
    IF p_id_socio IS NULL THEN
        RETURN;
    END IF;

    UPDATE public.socios s
       SET deuda_total = COALESCE((
               SELECT SUM(GREATEST(COALESCE(f.saldo_pendiente, f.total, 0), 0))
               FROM public.facturas f
               WHERE f.id_socio = p_id_socio
                 AND f.estado IN ('pendiente', 'parcial', 'vencida')
                 AND COALESCE(f.is_deleted, FALSE) = FALSE
           ), 0)::NUMERIC(12, 2),
           facturas_pendientes_count = COALESCE((
               SELECT COUNT(*)
               FROM public.facturas f
               WHERE f.id_socio = p_id_socio
                 AND f.estado IN ('pendiente', 'parcial', 'vencida')
                 AND COALESCE(f.is_deleted, FALSE) = FALSE
           ), 0),
           ultima_fecha_pago = (
               SELECT MAX(c.fecha_cobro)
               FROM public.cobros c
               JOIN public.facturas f ON f.id_factura = c.id_factura
               WHERE f.id_socio = p_id_socio
                 AND c.estado <> 'anulado'
                 AND COALESCE(c.is_deleted, FALSE) = FALSE
           ),
           updated_at = now()
     WHERE s.id_socio = p_id_socio;
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_after_cobro_balance()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_factura BIGINT;
    v_socio BIGINT;
BEGIN
    v_factura := COALESCE(NEW.id_factura, OLD.id_factura);

    PERFORM public.epsas_refresh_factura_balance(v_factura);

    SELECT id_socio INTO v_socio
    FROM public.facturas
    WHERE id_factura = v_factura;

    PERFORM public.epsas_refresh_socio_balance(v_socio);

    RETURN COALESCE(NEW, OLD);
END;
$$;

CREATE OR REPLACE FUNCTION public.epsas_after_factura_balance()
RETURNS TRIGGER
LANGUAGE plpgsql
SET search_path = public
AS $$
BEGIN
    PERFORM public.epsas_refresh_socio_balance(COALESCE(NEW.id_socio, OLD.id_socio));

    IF TG_OP = 'UPDATE' AND OLD.id_socio IS DISTINCT FROM NEW.id_socio THEN
        PERFORM public.epsas_refresh_socio_balance(OLD.id_socio);
    END IF;

    RETURN COALESCE(NEW, OLD);
END;
$$;

DROP TRIGGER IF EXISTS trg_epsas_cobro_balance ON public.cobros;
CREATE TRIGGER trg_epsas_cobro_balance
AFTER INSERT OR UPDATE OR DELETE ON public.cobros
FOR EACH ROW
EXECUTE FUNCTION public.epsas_after_cobro_balance();

DROP TRIGGER IF EXISTS trg_epsas_factura_balance ON public.facturas;
CREATE TRIGGER trg_epsas_factura_balance
AFTER INSERT OR UPDATE OR DELETE ON public.facturas
FOR EACH ROW
WHEN (pg_trigger_depth() < 2)
EXECUTE FUNCTION public.epsas_after_factura_balance();

UPDATE public.facturas f
   SET pagado_total = COALESCE(p.total_pagado, 0)::NUMERIC(12, 2),
       saldo_pendiente = GREATEST((COALESCE(f.total, 0) - COALESCE(p.total_pagado, 0)), 0)::NUMERIC(12, 2)
FROM (
    SELECT id_factura, SUM(monto_pagado) AS total_pagado
    FROM public.cobros
    WHERE estado <> 'anulado'
      AND COALESCE(is_deleted, FALSE) = FALSE
    GROUP BY id_factura
) p
WHERE p.id_factura = f.id_factura;

UPDATE public.facturas
   SET pagado_total = 0,
       saldo_pendiente = COALESCE(total, 0)::NUMERIC(12, 2)
 WHERE id_factura NOT IN (
    SELECT DISTINCT id_factura
    FROM public.cobros
    WHERE estado <> 'anulado'
      AND COALESCE(is_deleted, FALSE) = FALSE
 );

UPDATE public.socios s
   SET deuda_total = COALESCE(x.deuda_total, 0)::NUMERIC(12, 2),
       facturas_pendientes_count = COALESCE(x.facturas_pendientes_count, 0),
       ultima_fecha_pago = x.ultima_fecha_pago
FROM (
    SELECT
        s2.id_socio,
        SUM(GREATEST(COALESCE(f.saldo_pendiente, f.total, 0), 0)) FILTER (WHERE f.estado IN ('pendiente', 'parcial', 'vencida') AND COALESCE(f.is_deleted, FALSE) = FALSE) AS deuda_total,
        COUNT(f.id_factura) FILTER (WHERE f.estado IN ('pendiente', 'parcial', 'vencida') AND COALESCE(f.is_deleted, FALSE) = FALSE) AS facturas_pendientes_count,
        MAX(c.fecha_cobro) FILTER (WHERE c.estado <> 'anulado' AND COALESCE(c.is_deleted, FALSE) = FALSE) AS ultima_fecha_pago
    FROM public.socios s2
    LEFT JOIN public.facturas f ON f.id_socio = s2.id_socio
    LEFT JOIN public.cobros c ON c.id_factura = f.id_factura
    GROUP BY s2.id_socio
) x
WHERE x.id_socio = s.id_socio;

CREATE INDEX IF NOT EXISTS idx_facturas_pending_balance ON public.facturas (id_socio, estado, fecha_fin_cobro, id_factura) WHERE estado IN ('pendiente', 'parcial', 'vencida') AND is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_facturas_periodo_estado_tenant ON public.facturas (tenant_id, id_periodo, estado, fecha_emision DESC);
CREATE INDEX IF NOT EXISTS idx_facturas_saldo_pendiente ON public.facturas (saldo_pendiente DESC) WHERE saldo_pendiente > 0 AND is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_cobros_recaudacion_fecha ON public.cobros (tenant_id, fecha_cobro DESC, estado, origen_pago);
CREATE INDEX IF NOT EXISTS idx_cobros_factura_active ON public.cobros (id_factura, estado, fecha_cobro DESC) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_socios_deuda_estado ON public.socios (tenant_id, estado, deuda_total DESC, id_socio) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_socios_sector_estado ON public.socios (tenant_id, id_sector, estado, id_socio) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_personas_ci_lower ON public.personas (LOWER(cedula_identidad));
CREATE INDEX IF NOT EXISTS idx_personas_email_lower ON public.personas (LOWER(email)) WHERE email IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_users_email_lower ON public.users (LOWER(email));
CREATE INDEX IF NOT EXISTS idx_users_username_lower ON public.users (LOWER(username)) WHERE username IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_ordenes_pago_estado_expira ON public.ordenes_pago (estado, fecha_vencimiento, id_orden_pago) WHERE is_deleted = FALSE;
CREATE INDEX IF NOT EXISTS idx_historial_pagos_factura_fecha ON public.historial_pagos (id_factura, fecha_evento DESC);
CREATE INDEX IF NOT EXISTS idx_historial_pagos_socio_fecha ON public.historial_pagos (id_socio, fecha_evento DESC);

DROP VIEW IF EXISTS public.v_cobros_periodo_actual;
DROP VIEW IF EXISTS public.v_saldo_socios;
DROP VIEW IF EXISTS public.v_empleados;
DROP VIEW IF EXISTS public.v_socios;
DROP VIEW IF EXISTS public.vista_empleados_gestion;

DO $$
DECLARE
    view_name TEXT;
    views TEXT[] := ARRAY[
        'v_tecnico_lecturas_index',
        'v_tecnico_lecturas_recientes',
        'v_tecnico_medidores_consumo',
        'v_tecnico_ordenes_recientes',
        'v_tecnico_socios_catalogo'
    ];
BEGIN
    FOREACH view_name IN ARRAY views LOOP
        IF to_regclass(format('public.%I', view_name)) IS NOT NULL THEN
            EXECUTE format('ALTER VIEW public.%I SET (security_invoker = true)', view_name);
        END IF;
    END LOOP;
END;
$$;

CREATE OR REPLACE VIEW public.v_socios_estado_cuenta
WITH (security_invoker = true)
AS
SELECT
    s.id_socio,
    s.numero_socio,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    p.cedula_identidad,
    p.telefono,
    p.email,
    s.estado,
    s.deuda_total,
    s.facturas_pendientes_count,
    s.ultima_fecha_pago,
    s.id_sector,
    sec.nombre AS sector,
    s.id_tarifa,
    t.nombre AS tarifa,
    s.tenant_id,
    s.is_deleted
FROM public.socios s
JOIN public.personas p ON p.id_persona = s.id_persona
JOIN public.sectores sec ON sec.id_sector = s.id_sector
JOIN public.tarifas t ON t.id_tarifa = s.id_tarifa
WHERE COALESCE(s.is_deleted, FALSE) = FALSE;

CREATE OR REPLACE VIEW public.v_facturas_pendientes_caja
WITH (security_invoker = true)
AS
SELECT
    f.id_factura,
    f.numero_factura,
    f.id_socio,
    s.numero_socio,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    p.cedula_identidad,
    m.numero_serie AS numero_medidor,
    f.fecha_emision,
    f.fecha_inicio_cobro,
    f.fecha_fin_cobro,
    f.estado,
    f.total,
    f.pagado_total,
    f.saldo_pendiente,
    f.tenant_id
FROM public.facturas f
JOIN public.socios s ON s.id_socio = f.id_socio
JOIN public.personas p ON p.id_persona = s.id_persona
LEFT JOIN public.medidores m ON m.id_socio = s.id_socio AND m.estado = 'activo'
WHERE f.estado IN ('pendiente', 'parcial', 'vencida')
  AND COALESCE(f.is_deleted, FALSE) = FALSE
  AND COALESCE(s.is_deleted, FALSE) = FALSE;

CREATE OR REPLACE VIEW public.v_busqueda_operativa
WITH (security_invoker = true)
AS
SELECT
    'socio'::TEXT AS tipo,
    s.id_socio AS record_id,
    s.numero_socio AS codigo,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS titulo,
    concat_ws(' ', p.cedula_identidad, p.telefono, p.email, s.direccion, sec.nombre) AS detalle,
    s.search_vector || p.search_vector AS search_vector,
    s.tenant_id
FROM public.socios s
JOIN public.personas p ON p.id_persona = s.id_persona
LEFT JOIN public.sectores sec ON sec.id_sector = s.id_sector
WHERE COALESCE(s.is_deleted, FALSE) = FALSE
UNION ALL
SELECT
    'factura'::TEXT AS tipo,
    f.id_factura AS record_id,
    f.numero_factura AS codigo,
    s.numero_socio AS titulo,
    concat_ws(' ', f.estado, f.total::TEXT, f.saldo_pendiente::TEXT) AS detalle,
    f.search_vector,
    f.tenant_id
FROM public.facturas f
JOIN public.socios s ON s.id_socio = f.id_socio
WHERE COALESCE(f.is_deleted, FALSE) = FALSE
UNION ALL
SELECT
    'medidor'::TEXT AS tipo,
    m.id_medidor AS record_id,
    m.numero_serie AS codigo,
    s.numero_socio AS titulo,
    concat_ws(' ', m.marca, m.modelo, m.estado) AS detalle,
    m.search_vector,
    m.tenant_id
FROM public.medidores m
JOIN public.socios s ON s.id_socio = m.id_socio
WHERE COALESCE(m.is_deleted, FALSE) = FALSE;

DO $$
DECLARE
    table_name TEXT;
    tables TEXT[] := ARRAY[
        'organizations',
        'auditoria',
        'bank_payment_events',
        'cache',
        'cache_locks',
        'cierres_caja',
        'cobros',
        'comunicados',
        'empleados',
        'facturas',
        'failed_jobs',
        'gastos',
        'historial_pagos',
        'incidencias_tecnicas',
        'ingresos_administrativos',
        'job_batches',
        'jobs',
        'lecturas',
        'media_retention_events',
        'medidor_anomalias',
        'medidores',
        'metodos_pago',
        'migrations',
        'notificaciones',
        'operaciones_sistema',
        'orden_pago_detalles',
        'ordenes_pago',
        'ordenes_tecnicas',
        'password_reset_tokens',
        'periodos_facturacion',
        'permission_role',
        'personal_access_tokens',
        'personas',
        'reportes_tecnicos',
        'role_user',
        'roles',
        'sectores',
        'sessions',
        'sms_messages',
        'socios',
        'system_settings',
        'tarifas',
        'user_permissions',
        'user_roles',
        'users'
    ];
BEGIN
    FOREACH table_name IN ARRAY tables LOOP
        IF to_regclass(format('public.%I', table_name)) IS NOT NULL THEN
            EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', table_name);
        END IF;
    END LOOP;
END;
$$;

DO $$
BEGIN
    IF to_regclass('public.intentos_pago') IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.intentos_pago LIMIT 1) THEN
            DROP TABLE public.intentos_pago;
        END IF;
    END IF;
END;
$$;
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS public.v_busqueda_operativa;
DROP VIEW IF EXISTS public.v_facturas_pendientes_caja;
DROP VIEW IF EXISTS public.v_socios_estado_cuenta;

DROP TRIGGER IF EXISTS trg_epsas_cobro_balance ON public.cobros;
DROP TRIGGER IF EXISTS trg_epsas_factura_balance ON public.facturas;

DROP FUNCTION IF EXISTS public.epsas_after_cobro_balance();
DROP FUNCTION IF EXISTS public.epsas_after_factura_balance();
DROP FUNCTION IF EXISTS public.epsas_refresh_factura_balance(BIGINT);
DROP FUNCTION IF EXISTS public.epsas_refresh_socio_balance(BIGINT);

DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.personas;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.socios;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.medidores;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.facturas;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.gastos;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.ordenes_pago;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.comunicados;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.incidencias_tecnicas;
DROP TRIGGER IF EXISTS trg_epsas_search_vector ON public.ordenes_tecnicas;
DROP FUNCTION IF EXISTS public.epsas_refresh_search_vector();

DROP FUNCTION IF EXISTS public.epsas_touch_lifecycle_columns();
DROP FUNCTION IF EXISTS public.epsas_actor_user_id();
SQL);
    }
};
