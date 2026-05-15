BEGIN;

TRUNCATE TABLE
  auditoria,
  historial_pagos,
  notificaciones,
  cobros,
  facturas,
  lecturas,
  medidores,
  periodos_facturacion,
  metodos_pago,
  empleados,
  socios,
  tarifas,
  sectores,
  roles,
  personas
RESTART IDENTITY CASCADE;

INSERT INTO roles (id_rol, nombre, descripcion) VALUES
  (1, 'admin', 'Acceso total: configuracion, reportes, auditoria y gestion de usuarios'),
  (2, 'secretaria', 'Gestion de cobros, emision de facturas, socios y notificaciones'),
  (3, 'tecnico', 'Toma de lecturas de medidores en campo y actualizacion de su estado');

SELECT setval('roles_id_rol_seq', 3, true);

INSERT INTO sectores (id_sector, nombre, descripcion, zona) VALUES
  (1, 'Centro', 'Zona urbana central', 'Urbana'),
  (2, 'Norte', 'Barrios zona norte de la ciudad', 'Urbana'),
  (3, 'Sur', 'Barrios zona sur de la ciudad', 'Urbana'),
  (4, 'Rural Este', 'Comunidades rurales al este', 'Rural'),
  (5, 'Rural Oeste', 'Comunidades rurales al oeste', 'Rural');

SELECT setval('sectores_id_sector_seq', 5, true);

INSERT INTO tarifas (id_tarifa, nombre, precio_m3_base, consumo_minimo_m3, cargo_fijo, fecha_vigencia, estado) VALUES
  (1, 'Residencial Basica', 3.50, 5.00, 15.00, '2024-01-01', 'activa'),
  (2, 'Residencial Media', 4.20, 5.00, 20.00, '2024-01-01', 'activa'),
  (3, 'Comercial', 6.00, 10.00, 35.00, '2024-01-01', 'activa'),
  (4, 'Social', 1.80, 5.00, 8.00, '2024-01-01', 'activa'),
  (5, 'Antigua 2023', 3.00, 5.00, 12.00, '2023-01-01', 'inactiva');

SELECT setval('tarifas_id_tarifa_seq', 5, true);

INSERT INTO metodos_pago (id_metodo_pago, nombre, descripcion, requiere_referencia, estado) VALUES
  (1, 'Efectivo', 'Pago en efectivo en oficina', false, 'activo'),
  (2, 'Transferencia', 'Transferencia bancaria', true, 'activo'),
  (3, 'QR', 'Pago por codigo QR', true, 'activo'),
  (4, 'Cheque', 'Pago con cheque bancario', true, 'activo'),
  (5, 'Debito Automatico', 'Debito automatico de cuenta bancaria', true, 'inactivo');

SELECT setval('metodos_pago_id_metodo_pago_seq', 5, true);

INSERT INTO personas (id_persona, nombres, apellidos, cedula_identidad, telefono, email) VALUES
  (1, 'Carlos Alberto', 'Mamani Quispe', '1234567', '77712345', 'carlos.mamani@aguapotable.bo'),
  (2, 'Rosa Elena', 'Flores Choque', '2345678', '77723456', 'rosa.flores@aguapotable.bo'),
  (3, 'Pedro Luis', 'Condori Huanca', '3456789', '77734567', 'pedro.condori@aguapotable.bo'),
  (4, 'Juan Carlos', 'Perez Lopez', '5678901', '70011111', 'juan.perez@gmail.com'),
  (5, 'Maria Teresa', 'Villca Chura', '6789012', '70022222', 'maria.villca@gmail.com'),
  (6, 'Roberto', 'Aguilar Mendoza', '7890123', '70033333', NULL),
  (7, 'Carmen', 'Torrez Quispe', '8901234', '70044444', 'carmen.torrez@hotmail.com'),
  (8, 'Luis Fernando', 'Mamani Castro', '9012345', '70055555', NULL),
  (9, 'Silvia', 'Rojas Vargas', '9123456', '70066666', 'silvia.rojas@gmail.com'),
  (10, 'Hugo Daniel', 'Cespedes Nunez', '9234567', '70077777', NULL),
  (11, 'Beatriz', 'Choque Apaza', '9345678', '70088888', 'beatriz.choque@gmail.com');

SELECT setval('personas_id_persona_seq', 11, true);

INSERT INTO empleados (id_empleado, fecha_ingreso, estado, id_persona, id_rol, user_id) VALUES
  (1, '2022-03-01', 'activo', 1, 1, NULL),
  (2, '2022-05-15', 'activo', 2, 2, NULL),
  (3, '2023-01-10', 'activo', 3, 3, NULL);

SELECT setval('empleados_id_empleado_seq', 3, true);

INSERT INTO socios (id_socio, numero_socio, direccion, fecha_registro, estado, id_persona, id_sector, id_tarifa) VALUES
  (1, 'S-001', 'Calle Sucre #123', '2020-01-15', 'activo', 4, 1, 1),
  (2, 'S-002', 'Av. Heroinas #456', '2020-03-20', 'activo', 5, 1, 2),
  (3, 'S-003', 'Calle Bolivar #789', '2021-05-10', 'activo', 6, 2, 1),
  (4, 'S-004', 'Calle Espana #321', '2021-07-22', 'activo', 7, 2, 3),
  (5, 'S-005', 'Comunidad Las Flores s/n', '2022-02-01', 'activo', 8, 4, 4),
  (6, 'S-006', 'Av. Potosi #100', '2022-04-18', 'activo', 9, 3, 2),
  (7, 'S-007', 'Calle Junin #555', '2022-09-05', 'suspendido', 10, 3, 1),
  (8, 'S-008', 'Comunidad El Palmar km 12', '2023-01-12', 'activo', 11, 5, 4);

SELECT setval('socios_id_socio_seq', 8, true);

INSERT INTO medidores (id_medidor, numero_serie, marca, modelo, fecha_instalacion, estado, id_socio, id_empleado_instalador) VALUES
  (1, 'MED-2020-001', 'Zenner', 'MNK-V-2', '2020-01-20', 'activo', 1, 3),
  (2, 'MED-2020-002', 'Zenner', 'MNK-V-2', '2020-03-25', 'activo', 2, 3),
  (3, 'MED-2021-003', 'Itron', 'Actaris', '2021-05-15', 'activo', 3, 3),
  (4, 'MED-2021-004', 'Itron', 'Actaris', '2021-07-28', 'activo', 4, 3),
  (5, 'MED-2022-005', 'Elster', 'V100', '2022-02-05', 'activo', 5, 3),
  (6, 'MED-2022-006', 'Zenner', 'MNK-V-2', '2022-04-20', 'activo', 6, 3),
  (7, 'MED-2022-007', 'Elster', 'V100', '2022-09-10', 'activo', 7, 3),
  (8, 'MED-2023-008', 'Itron', 'Actaris', '2023-01-18', 'activo', 8, 3);

SELECT setval('medidores_id_medidor_seq', 8, true);

INSERT INTO periodos_facturacion (id_periodo, nombre, fecha_inicio, fecha_fin, cerrado) VALUES
  (1, 'Febrero 2025', '2025-02-01', '2025-02-28', true),
  (2, 'Marzo 2025', '2025-03-01', '2025-03-31', true),
  (3, 'Abril 2025', '2025-04-01', '2025-04-30', false);

SELECT setval('periodos_facturacion_id_periodo_seq', 3, true);

INSERT INTO lecturas (id_lectura, fecha_lectura, lectura_anterior, lectura_actual, observaciones, id_medidor, id_empleado) VALUES
  (1, '2025-02-05', 120.00, 135.00, NULL, 1, 3),
  (2, '2025-02-05', 200.00, 218.00, NULL, 2, 3),
  (3, '2025-02-06', 80.00, 92.00, NULL, 3, 3),
  (4, '2025-02-06', 340.00, 385.00, 'Local comercial', 4, 3),
  (5, '2025-02-07', 50.00, 58.00, NULL, 5, 3),
  (6, '2025-02-07', 175.00, 193.00, NULL, 6, 3),
  (7, '2025-02-08', 95.00, 107.00, 'Posible fuga menor', 7, 3),
  (8, '2025-02-08', 30.00, 38.00, NULL, 8, 3),
  (9, '2025-03-05', 135.00, 151.00, NULL, 1, 3),
  (10, '2025-03-05', 218.00, 240.00, NULL, 2, 3),
  (11, '2025-03-06', 92.00, 105.00, NULL, 3, 3),
  (12, '2025-03-06', 385.00, 438.00, NULL, 4, 3),
  (13, '2025-03-07', 58.00, 67.00, NULL, 5, 3),
  (14, '2025-03-07', 193.00, 214.00, NULL, 6, 3),
  (15, '2025-03-08', 107.00, 119.00, NULL, 7, 3),
  (16, '2025-03-08', 38.00, 47.00, NULL, 8, 3),
  (17, '2025-04-07', 151.00, 168.00, NULL, 1, 3),
  (18, '2025-04-07', 240.00, 261.00, NULL, 2, 3),
  (19, '2025-04-08', 105.00, 119.00, NULL, 3, 3),
  (20, '2025-04-08', 438.00, 492.00, NULL, 4, 3),
  (21, '2025-04-09', 67.00, 76.00, NULL, 5, 3),
  (22, '2025-04-09', 214.00, 235.00, NULL, 6, 3),
  (23, '2025-04-10', 119.00, 131.00, NULL, 7, 3),
  (24, '2025-04-10', 47.00, 57.00, NULL, 8, 3);

SELECT setval('lecturas_id_lectura_seq', 24, true);

INSERT INTO facturas (
  id_factura, numero_factura, fecha_emision, consumo_m3, monto_consumo,
  cargo_fijo, recargo_mora, descuentos, estado, id_socio, id_lectura, id_periodo
) VALUES
  (1, 'F-2025-0001', '2025-02-10', 15.00, 52.50, 15.00, 0.00, 0.00, 'pagada', 1, 1, 1),
  (2, 'F-2025-0002', '2025-02-10', 18.00, 75.60, 20.00, 0.00, 0.00, 'pagada', 2, 2, 1),
  (3, 'F-2025-0003', '2025-02-10', 12.00, 42.00, 15.00, 0.00, 0.00, 'pagada', 3, 3, 1),
  (4, 'F-2025-0004', '2025-02-10', 45.00, 270.00, 35.00, 0.00, 0.00, 'pagada', 4, 4, 1),
  (5, 'F-2025-0005', '2025-02-10', 8.00, 14.40, 8.00, 0.00, 0.00, 'pagada', 5, 5, 1),
  (6, 'F-2025-0006', '2025-02-10', 18.00, 75.60, 20.00, 0.00, 0.00, 'pagada', 6, 6, 1),
  (7, 'F-2025-0007', '2025-02-10', 12.00, 42.00, 15.00, 5.70, 0.00, 'vencida', 7, 7, 1),
  (8, 'F-2025-0008', '2025-02-10', 8.00, 14.40, 8.00, 0.00, 0.00, 'pagada', 8, 8, 1),
  (9, 'F-2025-0009', '2025-03-10', 16.00, 56.00, 15.00, 0.00, 0.00, 'pagada', 1, 9, 2),
  (10, 'F-2025-0010', '2025-03-10', 22.00, 92.40, 20.00, 0.00, 0.00, 'pagada', 2, 10, 2),
  (11, 'F-2025-0011', '2025-03-10', 13.00, 45.50, 15.00, 0.00, 0.00, 'parcial', 3, 11, 2),
  (12, 'F-2025-0012', '2025-03-10', 53.00, 318.00, 35.00, 0.00, 0.00, 'pendiente', 4, 12, 2),
  (13, 'F-2025-0013', '2025-03-10', 9.00, 16.20, 8.00, 0.00, 0.00, 'pagada', 5, 13, 2),
  (14, 'F-2025-0014', '2025-03-10', 21.00, 88.20, 20.00, 0.00, 0.00, 'pendiente', 6, 14, 2),
  (15, 'F-2025-0015', '2025-03-10', 12.00, 42.00, 15.00, 5.70, 0.00, 'vencida', 7, 15, 2),
  (16, 'F-2025-0016', '2025-03-10', 9.00, 16.20, 8.00, 0.00, 0.00, 'pagada', 8, 16, 2),
  (17, 'F-2025-0017', '2025-04-10', 17.00, 59.50, 15.00, 0.00, 0.00, 'pendiente', 1, 17, 3),
  (18, 'F-2025-0018', '2025-04-10', 21.00, 88.20, 20.00, 0.00, 0.00, 'pendiente', 2, 18, 3),
  (19, 'F-2025-0019', '2025-04-10', 14.00, 49.00, 15.00, 0.00, 0.00, 'pendiente', 3, 19, 3),
  (20, 'F-2025-0020', '2025-04-10', 54.00, 324.00, 35.00, 0.00, 0.00, 'pendiente', 4, 20, 3),
  (21, 'F-2025-0021', '2025-04-10', 9.00, 16.20, 8.00, 0.00, 0.00, 'pendiente', 5, 21, 3),
  (22, 'F-2025-0022', '2025-04-10', 21.00, 88.20, 20.00, 0.00, 0.00, 'pendiente', 6, 22, 3),
  (23, 'F-2025-0023', '2025-04-10', 10.00, 18.00, 8.00, 0.00, 0.00, 'pendiente', 8, 24, 3);

SELECT setval('facturas_id_factura_seq', 23, true);

UPDATE facturas f
SET precio_m3_aplicado = t.precio_m3_base,
    cargo_fijo_aplicado = t.cargo_fijo
FROM socios s
JOIN tarifas t ON t.id_tarifa = s.id_tarifa
WHERE s.id_socio = f.id_socio;

INSERT INTO cobros (id_cobro, fecha_cobro, monto_pagado, monto_pendiente, estado, comprobante, id_factura, id_metodo_pago, id_empleado) VALUES
  (1, '2025-02-12', 67.50, 0.00, 'completado', 'REC-001', 1, 1, 2),
  (2, '2025-02-12', 95.60, 0.00, 'completado', 'REC-002', 2, 1, 2),
  (3, '2025-02-13', 57.00, 0.00, 'completado', 'REC-003', 3, 3, 2),
  (4, '2025-02-13', 305.00, 0.00, 'completado', 'TRF-101', 4, 2, 2),
  (5, '2025-02-14', 22.40, 0.00, 'completado', 'REC-004', 5, 1, 2),
  (6, '2025-02-14', 95.60, 0.00, 'completado', 'QR-201', 6, 3, 2),
  (7, '2025-02-15', 22.40, 0.00, 'completado', 'REC-005', 8, 1, 2),
  (8, '2025-03-12', 71.00, 0.00, 'completado', 'REC-006', 9, 1, 2),
  (9, '2025-03-12', 112.40, 0.00, 'completado', 'QR-202', 10, 3, 2),
  (10, '2025-03-15', 24.20, 0.00, 'completado', 'REC-007', 13, 1, 2),
  (11, '2025-03-16', 24.20, 0.00, 'completado', 'REC-008', 16, 1, 2),
  (12, '2025-03-20', 30.00, 30.50, 'parcial', 'REC-009', 11, 1, 2);

SELECT setval('cobros_id_cobro_seq', 12, true);

INSERT INTO historial_pagos (tipo_evento, descripcion, monto, id_socio, id_factura, id_cobro, id_empleado) VALUES
  ('suspension', 'Socio suspendido por falta de pago acumulado', NULL, 7, NULL, NULL, 1),
  ('mora_aplicada', 'Recargo por mora aplicado a factura F-2025-0007', 5.70, 7, 7, NULL, 1),
  ('mora_aplicada', 'Recargo por mora aplicado a factura F-2025-0015', 5.70, 7, 15, NULL, 1);

INSERT INTO notificaciones (tipo, mensaje, enviado, canal, leido, prioridad, id_socio, id_factura) VALUES
  ('aviso_vencimiento', 'Estimado socio, su factura F-2025-0007 esta vencida. Regularice su pago.', true, 'sms', true, 'alta', 7, 7),
  ('aviso_vencimiento', 'Estimado socio, su factura F-2025-0015 esta vencida.', true, 'sms', false, 'alta', 7, 15),
  ('recordatorio', 'Su factura de Abril 2025 esta disponible. Total: Bs. 74.50', true, 'sms', false, 'media', 1, 17),
  ('recordatorio', 'Su factura de Abril 2025 esta disponible. Total: Bs. 108.20', true, 'email', false, 'media', 2, 18),
  ('recordatorio', 'Tiene un saldo pendiente de Bs. 30.50 de su factura de Marzo 2025.', true, 'sms', false, 'alta', 3, 11),
  ('aviso_corte', 'AVISO: Si no regulariza su deuda en 5 dias se procedera al corte.', false, 'sistema', false, 'alta', 7, NULL),
  ('recordatorio', 'Su factura de Abril 2025 esta disponible. Total: Bs. 359.00', true, 'whatsapp', false, 'media', 4, 20);

COMMIT;
