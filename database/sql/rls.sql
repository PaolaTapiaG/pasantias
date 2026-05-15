BEGIN;

DROP FUNCTION IF EXISTS get_mi_rol();
DROP FUNCTION IF EXISTS tiene_rol(VARIADIC TEXT[]);
DROP FUNCTION IF EXISTS get_mi_empleado_id();

CREATE OR REPLACE FUNCTION get_mi_rol()
RETURNS TEXT
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT r.nombre
  FROM empleados e
  JOIN roles r ON r.id_rol = e.id_rol
  WHERE e.user_id = auth.uid()
    AND e.estado = 'activo'
  LIMIT 1;
$$;

CREATE OR REPLACE FUNCTION tiene_rol(VARIADIC p_roles TEXT[])
RETURNS BOOLEAN
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT get_mi_rol() = ANY(p_roles);
$$;

CREATE OR REPLACE FUNCTION get_mi_empleado_id()
RETURNS BIGINT
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT id_empleado
  FROM empleados
  WHERE user_id = auth.uid()
    AND estado = 'activo'
  LIMIT 1;
$$;

ALTER TABLE personas ENABLE ROW LEVEL SECURITY;
ALTER TABLE roles ENABLE ROW LEVEL SECURITY;
ALTER TABLE sectores ENABLE ROW LEVEL SECURITY;
ALTER TABLE tarifas ENABLE ROW LEVEL SECURITY;
ALTER TABLE empleados ENABLE ROW LEVEL SECURITY;
ALTER TABLE socios ENABLE ROW LEVEL SECURITY;
ALTER TABLE medidores ENABLE ROW LEVEL SECURITY;
ALTER TABLE periodos_facturacion ENABLE ROW LEVEL SECURITY;
ALTER TABLE lecturas ENABLE ROW LEVEL SECURITY;
ALTER TABLE facturas ENABLE ROW LEVEL SECURITY;
ALTER TABLE metodos_pago ENABLE ROW LEVEL SECURITY;
ALTER TABLE cobros ENABLE ROW LEVEL SECURITY;
ALTER TABLE historial_pagos ENABLE ROW LEVEL SECURITY;
ALTER TABLE notificaciones ENABLE ROW LEVEL SECURITY;
ALTER TABLE auditoria ENABLE ROW LEVEL SECURITY;

DO $$
DECLARE
  rec RECORD;
BEGIN
  FOR rec IN
    SELECT policyname, tablename
    FROM pg_policies
    WHERE schemaname = 'public'
      AND tablename IN (
        'personas', 'roles', 'sectores', 'tarifas', 'empleados',
        'socios', 'medidores', 'periodos_facturacion', 'lecturas',
        'facturas', 'metodos_pago', 'cobros', 'historial_pagos',
        'notificaciones', 'auditoria'
      )
  LOOP
    EXECUTE format('DROP POLICY IF EXISTS %I ON %I', rec.policyname, rec.tablename);
  END LOOP;
END;
$$;

CREATE POLICY personas_select ON personas
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY personas_insert ON personas
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY personas_update ON personas
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY personas_delete ON personas
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY roles_select ON roles
  FOR SELECT USING (tiene_rol('admin', 'secretaria'));
CREATE POLICY roles_insert ON roles
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY roles_update ON roles
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY roles_delete ON roles
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY sectores_select ON sectores
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY sectores_insert ON sectores
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY sectores_update ON sectores
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY sectores_delete ON sectores
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY tarifas_select ON tarifas
  FOR SELECT USING (tiene_rol('admin', 'secretaria'));
CREATE POLICY tarifas_insert ON tarifas
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY tarifas_update ON tarifas
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY tarifas_delete ON tarifas
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY empleados_select_admin ON empleados
  FOR SELECT USING (tiene_rol('admin'));
CREATE POLICY empleados_select_self ON empleados
  FOR SELECT USING (
    tiene_rol('secretaria', 'tecnico')
    AND user_id = auth.uid()
  );
CREATE POLICY empleados_insert ON empleados
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY empleados_update_admin ON empleados
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY empleados_update_self ON empleados
  FOR UPDATE USING (
    tiene_rol('secretaria', 'tecnico')
    AND user_id = auth.uid()
  )
  WITH CHECK (
    tiene_rol('secretaria', 'tecnico')
    AND user_id = auth.uid()
  );
CREATE POLICY empleados_delete ON empleados
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY socios_select ON socios
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY socios_insert ON socios
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY socios_update ON socios
  FOR UPDATE USING (tiene_rol('admin', 'secretaria'))
  WITH CHECK (tiene_rol('admin', 'secretaria'));
CREATE POLICY socios_delete ON socios
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY medidores_select ON medidores
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY medidores_insert ON medidores
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY medidores_update ON medidores
  FOR UPDATE USING (tiene_rol('admin', 'tecnico'))
  WITH CHECK (tiene_rol('admin', 'tecnico'));
CREATE POLICY medidores_delete ON medidores
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY periodos_select ON periodos_facturacion
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY periodos_insert ON periodos_facturacion
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY periodos_update ON periodos_facturacion
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY periodos_delete ON periodos_facturacion
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY lecturas_select ON lecturas
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY lecturas_insert ON lecturas
  FOR INSERT WITH CHECK (
    tiene_rol('admin')
    OR (tiene_rol('tecnico') AND id_empleado = get_mi_empleado_id())
  );
CREATE POLICY lecturas_update_tecnico ON lecturas
  FOR UPDATE USING (
    tiene_rol('tecnico')
    AND id_empleado = get_mi_empleado_id()
    AND fecha_lectura = CURRENT_DATE
  )
  WITH CHECK (
    tiene_rol('tecnico')
    AND id_empleado = get_mi_empleado_id()
    AND fecha_lectura = CURRENT_DATE
  );
CREATE POLICY lecturas_update_admin ON lecturas
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY lecturas_delete ON lecturas
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY facturas_select ON facturas
  FOR SELECT USING (tiene_rol('admin', 'secretaria', 'tecnico'));
CREATE POLICY facturas_insert ON facturas
  FOR INSERT WITH CHECK (tiene_rol('admin', 'secretaria'));
CREATE POLICY facturas_update ON facturas
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY facturas_delete ON facturas
  FOR DELETE USING (false);

CREATE POLICY metodos_pago_select ON metodos_pago
  FOR SELECT USING (tiene_rol('admin', 'secretaria'));
CREATE POLICY metodos_pago_insert ON metodos_pago
  FOR INSERT WITH CHECK (tiene_rol('admin'));
CREATE POLICY metodos_pago_update ON metodos_pago
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY metodos_pago_delete ON metodos_pago
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY cobros_select_admin ON cobros
  FOR SELECT USING (tiene_rol('admin'));
CREATE POLICY cobros_select_secretaria ON cobros
  FOR SELECT USING (
    tiene_rol('secretaria')
    AND id_empleado = get_mi_empleado_id()
  );
CREATE POLICY cobros_insert ON cobros
  FOR INSERT WITH CHECK (
    tiene_rol('admin')
    OR (tiene_rol('secretaria') AND id_empleado = get_mi_empleado_id())
  );
CREATE POLICY cobros_update ON cobros
  FOR UPDATE USING (tiene_rol('admin')) WITH CHECK (tiene_rol('admin'));
CREATE POLICY cobros_delete ON cobros
  FOR DELETE USING (false);

CREATE POLICY historial_select_admin ON historial_pagos
  FOR SELECT USING (tiene_rol('admin'));
CREATE POLICY historial_select_secretaria ON historial_pagos
  FOR SELECT USING (
    tiene_rol('secretaria')
    AND id_empleado = get_mi_empleado_id()
  );
CREATE POLICY historial_insert ON historial_pagos
  FOR INSERT WITH CHECK (false);
CREATE POLICY historial_update ON historial_pagos
  FOR UPDATE USING (false);
CREATE POLICY historial_delete ON historial_pagos
  FOR DELETE USING (false);

CREATE POLICY notificaciones_select ON notificaciones
  FOR SELECT USING (tiene_rol('admin', 'secretaria'));
CREATE POLICY notificaciones_insert ON notificaciones
  FOR INSERT WITH CHECK (tiene_rol('admin', 'secretaria'));
CREATE POLICY notificaciones_update ON notificaciones
  FOR UPDATE USING (tiene_rol('admin', 'secretaria'))
  WITH CHECK (tiene_rol('admin', 'secretaria'));
CREATE POLICY notificaciones_delete ON notificaciones
  FOR DELETE USING (tiene_rol('admin'));

CREATE POLICY auditoria_select ON auditoria
  FOR SELECT USING (tiene_rol('admin'));
CREATE POLICY auditoria_insert ON auditoria
  FOR INSERT WITH CHECK (false);
CREATE POLICY auditoria_update ON auditoria
  FOR UPDATE USING (false);
CREATE POLICY auditoria_delete ON auditoria
  FOR DELETE USING (false);

COMMIT;
