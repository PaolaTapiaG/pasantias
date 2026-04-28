<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE auditoria
  ADD COLUMN IF NOT EXISTS id_empleado BIGINT,
  ADD COLUMN IF NOT EXISTS id_socio BIGINT,
  ADD COLUMN IF NOT EXISTS id_factura BIGINT,
  ADD COLUMN IF NOT EXISTS id_cobro BIGINT,
  ADD COLUMN IF NOT EXISTS id_tarifa BIGINT;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'auditoria_id_empleado_foreign'
  ) THEN
    ALTER TABLE auditoria
      ADD CONSTRAINT auditoria_id_empleado_foreign
      FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'auditoria_id_socio_foreign'
  ) THEN
    ALTER TABLE auditoria
      ADD CONSTRAINT auditoria_id_socio_foreign
      FOREIGN KEY (id_socio) REFERENCES socios(id_socio);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'auditoria_id_factura_foreign'
  ) THEN
    ALTER TABLE auditoria
      ADD CONSTRAINT auditoria_id_factura_foreign
      FOREIGN KEY (id_factura) REFERENCES facturas(id_factura);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'auditoria_id_cobro_foreign'
  ) THEN
    ALTER TABLE auditoria
      ADD CONSTRAINT auditoria_id_cobro_foreign
      FOREIGN KEY (id_cobro) REFERENCES cobros(id_cobro);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'auditoria_id_tarifa_foreign'
  ) THEN
    ALTER TABLE auditoria
      ADD CONSTRAINT auditoria_id_tarifa_foreign
      FOREIGN KEY (id_tarifa) REFERENCES tarifas(id_tarifa);
  END IF;
END
$$;

CREATE INDEX IF NOT EXISTS idx_auditoria_empleado ON auditoria(id_empleado);
CREATE INDEX IF NOT EXISTS idx_auditoria_socio ON auditoria(id_socio);
CREATE INDEX IF NOT EXISTS idx_auditoria_factura ON auditoria(id_factura);
CREATE INDEX IF NOT EXISTS idx_auditoria_cobro ON auditoria(id_cobro);
CREATE INDEX IF NOT EXISTS idx_auditoria_tarifa ON auditoria(id_tarifa);

DROP TRIGGER IF EXISTS trg_auditoria_facturas ON facturas;
DROP TRIGGER IF EXISTS trg_auditoria_cobros ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_socios ON socios;
DROP TRIGGER IF EXISTS trg_auditoria_tarifas ON tarifas;
DROP FUNCTION IF EXISTS auditoria_trigger();

CREATE OR REPLACE FUNCTION auditoria_trigger()
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
BEGIN
  v_registro := CASE
    WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD)
    ELSE to_jsonb(NEW)
  END;

  SELECT e.id_empleado
    INTO v_actor_empleado_id
  FROM empleados e
  WHERE e.user_id = auth.uid()
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
    FROM facturas f
    WHERE f.id_factura = v_factura_id;
  ELSIF TG_TABLE_NAME = 'tarifas' THEN
    v_tarifa_id := (v_registro->>'id_tarifa')::BIGINT;
  END IF;

  CASE TG_OP
    WHEN 'INSERT' THEN
      INSERT INTO auditoria(
        tabla, accion, usuario, id_empleado, id_socio, id_factura, id_cobro, id_tarifa, datos_despues
      )
      VALUES (
        TG_TABLE_NAME, TG_OP, auth.uid()::text, v_actor_empleado_id, v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id, to_jsonb(NEW)
      );
    WHEN 'UPDATE' THEN
      INSERT INTO auditoria(
        tabla, accion, usuario, id_empleado, id_socio, id_factura, id_cobro, id_tarifa, datos_antes, datos_despues
      )
      VALUES (
        TG_TABLE_NAME, TG_OP, auth.uid()::text, v_actor_empleado_id, v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id, to_jsonb(OLD), to_jsonb(NEW)
      );
    WHEN 'DELETE' THEN
      INSERT INTO auditoria(
        tabla, accion, usuario, id_empleado, id_socio, id_factura, id_cobro, id_tarifa, datos_antes
      )
      VALUES (
        TG_TABLE_NAME, TG_OP, auth.uid()::text, v_actor_empleado_id, v_socio_id, v_factura_id, v_cobro_id, v_tarifa_id, to_jsonb(OLD)
      );
      RETURN OLD;
  END CASE;

  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_auditoria_facturas
  AFTER INSERT OR UPDATE OR DELETE ON facturas
  FOR EACH ROW
  EXECUTE FUNCTION auditoria_trigger();

CREATE TRIGGER trg_auditoria_cobros
  AFTER INSERT OR UPDATE OR DELETE ON cobros
  FOR EACH ROW
  EXECUTE FUNCTION auditoria_trigger();

CREATE TRIGGER trg_auditoria_socios
  AFTER INSERT OR UPDATE OR DELETE ON socios
  FOR EACH ROW
  EXECUTE FUNCTION auditoria_trigger();

CREATE TRIGGER trg_auditoria_tarifas
  AFTER INSERT OR UPDATE OR DELETE ON tarifas
  FOR EACH ROW
  EXECUTE FUNCTION auditoria_trigger();
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_auditoria_facturas ON facturas;
DROP TRIGGER IF EXISTS trg_auditoria_cobros ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_socios ON socios;
DROP TRIGGER IF EXISTS trg_auditoria_tarifas ON tarifas;
DROP FUNCTION IF EXISTS auditoria_trigger();

DROP INDEX IF EXISTS idx_auditoria_empleado;
DROP INDEX IF EXISTS idx_auditoria_socio;
DROP INDEX IF EXISTS idx_auditoria_factura;
DROP INDEX IF EXISTS idx_auditoria_cobro;
DROP INDEX IF EXISTS idx_auditoria_tarifa;

ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_id_empleado_foreign;
ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_id_socio_foreign;
ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_id_factura_foreign;
ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_id_cobro_foreign;
ALTER TABLE auditoria DROP CONSTRAINT IF EXISTS auditoria_id_tarifa_foreign;

ALTER TABLE auditoria
  DROP COLUMN IF EXISTS id_empleado,
  DROP COLUMN IF EXISTS id_socio,
  DROP COLUMN IF EXISTS id_factura,
  DROP COLUMN IF EXISTS id_cobro,
  DROP COLUMN IF EXISTS id_tarifa;
SQL);
    }
};
