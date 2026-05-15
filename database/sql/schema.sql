BEGIN;

DROP VIEW IF EXISTS v_cobros_periodo_actual;
DROP VIEW IF EXISTS v_saldo_socios;
DROP VIEW IF EXISTS v_empleados;
DROP VIEW IF EXISTS v_socios;

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

CREATE TABLE personas (
  id_persona BIGSERIAL PRIMARY KEY,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NOT NULL,
  cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
  telefono VARCHAR(20),
  email VARCHAR(150) UNIQUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE roles (
  id_rol BIGSERIAL PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE sectores (
  id_sector BIGSERIAL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT,
  zona VARCHAR(80),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE tarifas (
  id_tarifa BIGSERIAL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio_m3_base NUMERIC(10,4) NOT NULL,
  consumo_minimo_m3 NUMERIC(10,2) NOT NULL DEFAULT 0,
  cargo_fijo NUMERIC(10,2) NOT NULL DEFAULT 0,
  fecha_vigencia DATE NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'activa'
    CHECK (estado IN ('activa', 'inactiva')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE metodos_pago (
  id_metodo_pago BIGSERIAL PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion TEXT,
  requiere_referencia BOOLEAN NOT NULL DEFAULT FALSE,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
    CHECK (estado IN ('activo', 'inactivo')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE empleados (
  id_empleado BIGSERIAL PRIMARY KEY,
  fecha_ingreso DATE NOT NULL DEFAULT CURRENT_DATE,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
    CHECK (estado IN ('activo', 'inactivo', 'suspendido')),
  id_persona BIGINT NOT NULL UNIQUE REFERENCES personas(id_persona),
  id_rol BIGINT NOT NULL REFERENCES roles(id_rol),
  user_id UUID REFERENCES auth.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE socios (
  id_socio BIGSERIAL PRIMARY KEY,
  numero_socio VARCHAR(30) NOT NULL UNIQUE,
  direccion TEXT,
  fecha_registro DATE NOT NULL DEFAULT CURRENT_DATE,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
    CHECK (estado IN ('activo', 'inactivo', 'suspendido', 'cortado')),
  id_persona BIGINT NOT NULL UNIQUE REFERENCES personas(id_persona),
  id_sector BIGINT NOT NULL REFERENCES sectores(id_sector),
  id_tarifa BIGINT NOT NULL REFERENCES tarifas(id_tarifa),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE medidores (
  id_medidor BIGSERIAL PRIMARY KEY,
  numero_serie VARCHAR(60) NOT NULL UNIQUE,
  marca VARCHAR(80),
  modelo VARCHAR(80),
  fecha_instalacion DATE NOT NULL DEFAULT CURRENT_DATE,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
    CHECK (estado IN ('activo', 'inactivo', 'danado', 'reemplazado')),
  id_socio BIGINT NOT NULL REFERENCES socios(id_socio),
  id_empleado_instalador BIGINT REFERENCES empleados(id_empleado),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE periodos_facturacion (
  id_periodo BIGSERIAL PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  cerrado BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT chk_periodo_fechas CHECK (fecha_fin >= fecha_inicio)
);

CREATE TABLE lecturas (
  id_lectura BIGSERIAL PRIMARY KEY,
  fecha_lectura DATE NOT NULL DEFAULT CURRENT_DATE,
  lectura_anterior NUMERIC(12,2) NOT NULL,
  lectura_actual NUMERIC(12,2) NOT NULL,
  consumo_m3 NUMERIC(12,2) GENERATED ALWAYS AS
    (lectura_actual - lectura_anterior) STORED,
  observaciones TEXT,
  id_medidor BIGINT NOT NULL REFERENCES medidores(id_medidor),
  id_empleado BIGINT NOT NULL REFERENCES empleados(id_empleado),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT chk_lectura_positiva CHECK (lectura_actual >= lectura_anterior),
  CONSTRAINT unique_lectura_periodo UNIQUE (id_medidor, fecha_lectura)
);

CREATE TABLE facturas (
  id_factura BIGSERIAL PRIMARY KEY,
  numero_factura VARCHAR(30) NOT NULL UNIQUE,
  fecha_emision DATE NOT NULL DEFAULT CURRENT_DATE,
  fecha_pago DATE,
  consumo_m3 NUMERIC(12,2) NOT NULL,
  monto_consumo NUMERIC(12,2) NOT NULL,
  cargo_fijo NUMERIC(12,2) NOT NULL DEFAULT 0,
  recargo_mora NUMERIC(12,2) NOT NULL DEFAULT 0,
  descuentos NUMERIC(12,2) NOT NULL DEFAULT 0,
  total NUMERIC(12,2) GENERATED ALWAYS AS
    (monto_consumo + cargo_fijo + recargo_mora - descuentos) STORED,
  precio_m3_aplicado NUMERIC(10,4),
  cargo_fijo_aplicado NUMERIC(10,2),
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
    CHECK (estado IN ('pendiente', 'pagada', 'vencida', 'anulada', 'parcial')),
  id_socio BIGINT NOT NULL REFERENCES socios(id_socio),
  id_lectura BIGINT NOT NULL REFERENCES lecturas(id_lectura),
  id_periodo BIGINT NOT NULL REFERENCES periodos_facturacion(id_periodo),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT unique_factura_periodo UNIQUE (id_socio, id_periodo)
);

CREATE TABLE cobros (
  id_cobro BIGSERIAL PRIMARY KEY,
  fecha_cobro DATE NOT NULL DEFAULT CURRENT_DATE,
  monto_pagado NUMERIC(12,2) NOT NULL,
  monto_pendiente NUMERIC(12,2) NOT NULL DEFAULT 0,
  estado VARCHAR(20) NOT NULL DEFAULT 'completado'
    CHECK (estado IN ('completado', 'parcial', 'anulado')),
  comprobante VARCHAR(100),
  id_factura BIGINT NOT NULL REFERENCES facturas(id_factura),
  id_metodo_pago BIGINT NOT NULL REFERENCES metodos_pago(id_metodo_pago),
  id_empleado BIGINT NOT NULL REFERENCES empleados(id_empleado),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE historial_pagos (
  id_historial BIGSERIAL PRIMARY KEY,
  fecha_evento TIMESTAMPTZ NOT NULL DEFAULT now(),
  tipo_evento VARCHAR(60) NOT NULL,
  descripcion TEXT,
  monto NUMERIC(12,2),
  id_socio BIGINT NOT NULL REFERENCES socios(id_socio),
  id_factura BIGINT REFERENCES facturas(id_factura),
  id_cobro BIGINT REFERENCES cobros(id_cobro),
  id_empleado BIGINT REFERENCES empleados(id_empleado),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE notificaciones (
  id_notificacion BIGSERIAL PRIMARY KEY,
  tipo VARCHAR(60) NOT NULL,
  mensaje TEXT NOT NULL,
  fecha_envio TIMESTAMPTZ NOT NULL DEFAULT now(),
  enviado BOOLEAN NOT NULL DEFAULT FALSE,
  canal VARCHAR(30) NOT NULL DEFAULT 'sms'
    CHECK (canal IN ('sms', 'email', 'whatsapp', 'sistema')),
  leido BOOLEAN NOT NULL DEFAULT FALSE,
  prioridad VARCHAR(20) NOT NULL DEFAULT 'media'
    CHECK (prioridad IN ('alta', 'media', 'baja')),
  id_socio BIGINT NOT NULL REFERENCES socios(id_socio),
  id_factura BIGINT REFERENCES facturas(id_factura),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE auditoria (
  id_auditoria BIGSERIAL PRIMARY KEY,
  tabla VARCHAR(50) NOT NULL,
  accion VARCHAR(20) NOT NULL,
  usuario TEXT DEFAULT current_user,
  id_empleado BIGINT REFERENCES empleados(id_empleado),
  id_socio BIGINT REFERENCES socios(id_socio),
  id_factura BIGINT REFERENCES facturas(id_factura),
  id_cobro BIGINT REFERENCES cobros(id_cobro),
  id_tarifa BIGINT REFERENCES tarifas(id_tarifa),
  fecha TIMESTAMPTZ DEFAULT now(),
  datos_antes JSONB,
  datos_despues JSONB
);

CREATE OR REPLACE VIEW v_socios AS
SELECT
  s.id_socio,
  s.numero_socio,
  p.nombres,
  p.apellidos,
  p.cedula_identidad,
  p.telefono,
  p.email,
  s.direccion,
  s.fecha_registro,
  s.estado,
  sec.nombre AS sector,
  t.nombre AS tarifa,
  t.precio_m3_base,
  t.cargo_fijo
FROM socios s
JOIN personas p ON p.id_persona = s.id_persona
JOIN sectores sec ON sec.id_sector = s.id_sector
JOIN tarifas t ON t.id_tarifa = s.id_tarifa;

CREATE OR REPLACE VIEW v_empleados AS
SELECT
  e.id_empleado,
  p.nombres,
  p.apellidos,
  p.cedula_identidad,
  p.telefono,
  p.email,
  e.fecha_ingreso,
  e.estado,
  r.nombre AS rol
FROM empleados e
JOIN personas p ON p.id_persona = e.id_persona
JOIN roles r ON r.id_rol = e.id_rol;

CREATE OR REPLACE VIEW v_saldo_socios AS
SELECT
  s.id_socio,
  p.nombres || ' ' || p.apellidos AS socio,
  s.numero_socio,
  COUNT(f.id_factura) AS facturas_pendientes,
  COALESCE(SUM(
    f.total - COALESCE((
      SELECT SUM(c.monto_pagado)
      FROM cobros c
      WHERE c.id_factura = f.id_factura
        AND c.estado <> 'anulado'
    ), 0)
  ), 0) AS saldo_pendiente
FROM socios s
JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN facturas f ON f.id_socio = s.id_socio
  AND f.estado IN ('pendiente', 'parcial', 'vencida')
GROUP BY s.id_socio, p.nombres, p.apellidos, s.numero_socio;

CREATE OR REPLACE VIEW v_cobros_periodo_actual AS
SELECT
  pf.nombre AS periodo,
  COUNT(c.id_cobro) AS total_cobros,
  SUM(c.monto_pagado) AS monto_recaudado,
  e.id_empleado,
  per.nombres || ' ' || per.apellidos AS empleado
FROM cobros c
JOIN facturas f ON f.id_factura = c.id_factura
JOIN periodos_facturacion pf ON pf.id_periodo = f.id_periodo
JOIN empleados e ON e.id_empleado = c.id_empleado
JOIN personas per ON per.id_persona = e.id_persona
WHERE pf.cerrado = FALSE
GROUP BY pf.nombre, e.id_empleado, per.nombres, per.apellidos;

CREATE INDEX idx_personas_ci ON personas(cedula_identidad);
CREATE INDEX idx_socios_numero ON socios(numero_socio);
CREATE INDEX idx_socios_persona ON socios(id_persona);
CREATE INDEX idx_empleados_persona ON empleados(id_persona);
CREATE INDEX idx_empleados_user ON empleados(user_id);
CREATE INDEX idx_medidores_socio ON medidores(id_socio);
CREATE INDEX idx_lecturas_medidor ON lecturas(id_medidor);
CREATE INDEX idx_lecturas_fecha ON lecturas(fecha_lectura);
CREATE INDEX idx_facturas_socio ON facturas(id_socio);
CREATE INDEX idx_facturas_estado ON facturas(estado);
CREATE INDEX idx_facturas_periodo ON facturas(id_periodo);
CREATE INDEX idx_cobros_factura ON cobros(id_factura);
CREATE INDEX idx_cobros_fecha ON cobros(fecha_cobro);
CREATE INDEX idx_historial_socio ON historial_pagos(id_socio);
CREATE INDEX idx_historial_factura ON historial_pagos(id_factura);
CREATE INDEX idx_notificaciones_socio ON notificaciones(id_socio);
CREATE INDEX idx_auditoria_empleado ON auditoria(id_empleado);
CREATE INDEX idx_auditoria_socio ON auditoria(id_socio);
CREATE INDEX idx_auditoria_factura ON auditoria(id_factura);
CREATE INDEX idx_auditoria_cobro ON auditoria(id_cobro);
CREATE INDEX idx_auditoria_tarifa ON auditoria(id_tarifa);

COMMIT;
