BEGIN;

DROP TRIGGER IF EXISTS trg_tarifa_factura ON facturas;
DROP TRIGGER IF EXISTS trg_validar_pago ON cobros;
DROP TRIGGER IF EXISTS trg_actualizar_estado_factura ON cobros;
DROP TRIGGER IF EXISTS trg_historial_cobro ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_facturas ON facturas;
DROP TRIGGER IF EXISTS trg_auditoria_cobros ON cobros;
DROP TRIGGER IF EXISTS trg_auditoria_socios ON socios;
DROP TRIGGER IF EXISTS trg_auditoria_tarifas ON tarifas;

DROP FUNCTION IF EXISTS guardar_tarifa_factura();
DROP FUNCTION IF EXISTS validar_pago_factura();
DROP FUNCTION IF EXISTS actualizar_estado_factura();
DROP FUNCTION IF EXISTS registrar_historial_cobro();
DROP FUNCTION IF EXISTS marcar_facturas_vencidas();
DROP FUNCTION IF EXISTS auditoria_trigger();

CREATE OR REPLACE FUNCTION guardar_tarifa_factura()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
  SELECT t.precio_m3_base, t.cargo_fijo
    INTO NEW.precio_m3_aplicado, NEW.cargo_fijo_aplicado
  FROM tarifas t
  JOIN socios s ON s.id_tarifa = t.id_tarifa
  WHERE s.id_socio = NEW.id_socio;

  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_tarifa_factura
  BEFORE INSERT ON facturas
  FOR EACH ROW
  EXECUTE FUNCTION guardar_tarifa_factura();

CREATE OR REPLACE FUNCTION validar_pago_factura()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
  v_total_pagado NUMERIC;
  v_total_factura NUMERIC;
BEGIN
  SELECT COALESCE(SUM(monto_pagado), 0)
    INTO v_total_pagado
  FROM cobros
  WHERE id_factura = NEW.id_factura
    AND estado <> 'anulado';

  SELECT total
    INTO v_total_factura
  FROM facturas
  WHERE id_factura = NEW.id_factura
  FOR UPDATE;

  IF (v_total_pagado + NEW.monto_pagado) > v_total_factura THEN
    RAISE EXCEPTION
      'El pago (%) excede el saldo pendiente (%) de la factura %',
      NEW.monto_pagado,
      (v_total_factura - v_total_pagado),
      NEW.id_factura;
  END IF;

  NEW.monto_pendiente := GREATEST(v_total_factura - (v_total_pagado + NEW.monto_pagado), 0);

  IF NEW.estado <> 'anulado' THEN
    IF NEW.monto_pendiente > 0 THEN
      NEW.estado := 'parcial';
    ELSE
      NEW.estado := 'completado';
    END IF;
  END IF;

  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_validar_pago
  BEFORE INSERT ON cobros
  FOR EACH ROW
  EXECUTE FUNCTION validar_pago_factura();

CREATE OR REPLACE FUNCTION actualizar_estado_factura()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  v_total_pagado NUMERIC;
  v_total_factura NUMERIC;
BEGIN
  SELECT COALESCE(SUM(monto_pagado), 0)
    INTO v_total_pagado
  FROM cobros
  WHERE id_factura = NEW.id_factura
    AND estado <> 'anulado';

  SELECT total
    INTO v_total_factura
  FROM facturas
  WHERE id_factura = NEW.id_factura;

  IF v_total_pagado = 0 THEN
    UPDATE facturas
    SET estado = 'pendiente',
        fecha_pago = NULL,
        updated_at = now()
    WHERE id_factura = NEW.id_factura;
  ELSIF v_total_pagado < v_total_factura THEN
    UPDATE facturas
    SET estado = 'parcial',
        fecha_pago = NULL,
        updated_at = now()
    WHERE id_factura = NEW.id_factura;
  ELSE
    UPDATE facturas
    SET estado = 'pagada',
        fecha_pago = CURRENT_DATE,
        updated_at = now()
    WHERE id_factura = NEW.id_factura;
  END IF;

  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_actualizar_estado_factura
  AFTER INSERT ON cobros
  FOR EACH ROW
  EXECUTE FUNCTION actualizar_estado_factura();

CREATE OR REPLACE FUNCTION registrar_historial_cobro()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  v_id_socio BIGINT;
BEGIN
  SELECT id_socio
    INTO v_id_socio
  FROM facturas
  WHERE id_factura = NEW.id_factura;

  INSERT INTO historial_pagos (
    tipo_evento,
    descripcion,
    monto,
    id_socio,
    id_factura,
    id_cobro,
    id_empleado,
    created_at,
    updated_at
  ) VALUES (
    'cobro_registrado',
    'Cobro #' || NEW.id_cobro || ' - metodo pago id: ' || NEW.id_metodo_pago,
    NEW.monto_pagado,
    v_id_socio,
    NEW.id_factura,
    NEW.id_cobro,
    NEW.id_empleado,
    now(),
    now()
  );

  RETURN NEW;
END;
$$;

CREATE TRIGGER trg_historial_cobro
  AFTER INSERT ON cobros
  FOR EACH ROW
  EXECUTE FUNCTION registrar_historial_cobro();

CREATE OR REPLACE FUNCTION marcar_facturas_vencidas()
RETURNS INTEGER
LANGUAGE plpgsql
AS $$
DECLARE
  v_total INTEGER;
BEGIN
  UPDATE facturas
  SET estado = 'vencida',
      updated_at = now()
  WHERE estado IN ('pendiente', 'parcial')
    AND id_periodo IN (
      SELECT id_periodo
      FROM periodos_facturacion
      WHERE fecha_fin < CURRENT_DATE
        AND cerrado = TRUE
    );

  GET DIAGNOSTICS v_total = ROW_COUNT;
  RETURN v_total;
END;
$$;

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

COMMIT;
