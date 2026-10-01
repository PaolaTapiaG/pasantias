# Auditoria de deuda tecnica

Fecha de corte: 21 de septiembre de 2026

Este inventario refleja el estado actual del CRM despues de retirar el portal publico de usuarios. Se contrastaron los analisis historicos con el codigo, las rutas, las dependencias y la suite de pruebas actuales.

## Revision integral 22 de septiembre de 2026

- Arquitectura: encaminada, pero la complejidad sigue concentrada en `TechnicalPanelService`, `PaymentOrderService` y `BillingAutomationService`; QR y consultas de caja ya fueron extraidos.
- Operacion web: la sincronizacion pesada de facturas se ejecuta por scheduler cada 15 minutos con `withoutOverlapping`; no se encontraron llamadas actuales desde controladores web.
- Pruebas: 47 pruebas y 120 aserciones pasan; cubren integridad, webhook bancario, cobros, seguridad, roles y caché, pero no representan todavía toda la matriz de rutas por rol ni concurrencia.
- Rendimiento: no demostrado para el CRM actual. El arnés apuntó inicialmente a rutas del portal retirado y el intento contra `127.0.0.1:8080` no encontró un staging activo.
- Producción: bloqueada hasta ejecutar el checklist en infraestructura equivalente; la auditoría contable local está limpia y las migraciones están al día, pero faltan evidencia de respaldo restaurado, Redis, monitoreo, correo, webhook, QR y usuario PostgreSQL limitado.
- Negocio: facturación, cobros, órdenes QR, confirmación bancaria, caja y lecturas tienen reglas y pruebas parciales; faltan pruebas de anulaciones, cierres, pagos parciales, doble ejecución concurrente y conciliación operativa completa.
- Índices y tablas: las tablas críticas existen, las migraciones históricas de rendimiento están aplicadas y se consolidaron índices B-tree duplicados en facturas, cobros, lecturas, socios, personas y medidores mediante migraciones concurrentes.
- Observabilidad de esquema: `ext-intl` quedó habilitado en la CLI local de PHP y ya no aparece como bloqueador en producción.
- Salud PostgreSQL observada: 7 conexiones activas, 0 deadlocks, 0 archivos temporales y aproximadamente 99,4% de aciertos de buffer en la base consultada. Parámetros actuales: `shared_buffers=224MB`, `work_mem=2184kB`, `maintenance_work_mem=32MB`, `effective_cache_size=384MB`, `max_connections=60`, `jit=off`.
- Interpretación: no hay evidencia suficiente de saturación general de PostgreSQL; la lentitud debe aislarse con planes `EXPLAIN`, estadísticas de tablas/índices y medición HTTP del CRM antes de cambiar memoria o conexiones.

## Resumen ejecutivo

La deuda tecnica mas urgente no esta en una funcion aislada: esta concentrada en seguridad de dependencias, preparacion operativa, consultas y cache de datos financieros, exportaciones grandes, complejidad de servicios y cobertura de autorizacion.

Estado de prioridad:

| Prioridad | Deuda | Impacto |
| --- | --- | --- |
| P0 resuelto | Dependencia PHP con avisos de seguridad | DoS y bypass de filtros al procesar Markdown |
| P0 | Controles de produccion aun no demostrados | Riesgo de perdida de datos, indisponibilidad y despliegue inseguro |
| P1 reducido | Exportaciones materializadas en memoria | Timeouts y agotamiento de memoria |
| P1 controlado | Cache financiera con TTL de 10 minutos e invalidacion por dominio | Datos administrativos desactualizados |
| P1 reducido | Complejidad concentrada en servicios/controladores | Cambios lentos y regresiones dificiles de detectar |
| P1 parcial | Pruebas insuficientes de roles y flujos criticos | Fallos de autorizacion o negocio sin detectar |
| P2 resuelto | Dependencia JS vulnerable transitoria | Riesgo en la cadena de build |
| P2 resuelto | Logout por GET sin CSRF | Cierre de sesiones inducido por terceros |
| P2 minimo residual | Compatibilidad legacy de nombres de roles | Clases antiguas disponibles hasta retirar consumidores externos |
| P2 minimo residual | Requisito PostgreSQL y SQL especifico | Portabilidad fuera del motor soportado no garantizada |
| P2 resuelto | Documentacion desactualizada | Decisiones basadas en arquitectura que ya no existe |
| P3 parcial | Defaults de configuracion permisivos | Despliegues incompletos o inseguros por variables omitidas |

## Hallazgos vigentes

### P0-01. Dependencia PHP vulnerable (resuelto)

- Evidencia inicial: `composer audit` reportaba 10 avisos sobre `league/commonmark` version `2.8.2`.
- Correccion aplicada: `league/commonmark` se fijo en `^2.10.3`; Composer actualizo el lockfile y `composer audit` ya termina sin avisos.
- Avisos: varios DoS al analizar Markdown y un bypass de filtros de enlaces/atributos.
- Riesgo: si cualquier contenido controlado por usuarios llega a un parser Markdown, puede provocar consumo excesivo de CPU o salida insegura.
- Accion restante: revisar usos de Markdown y ejecutar pruebas de contenido enriquecido si se incorpora contenido Markdown controlado por usuarios.
- Validacion: `composer audit --format=plain` termina sin avisos.

### P0-02. Produccion no demostrada en infraestructura final

- Evidencia: [`ProductionReadinessCheck.php`](app/Console/Commands/ProductionReadinessCheck.php) bloquea HTTPS, cookies seguras, Redis, correo real, respaldo restaurado, monitoreo externo, webhook bancario, QR oficial, usuario PostgreSQL limitado, conexiones PostgreSQL persistentes, credenciales por defecto, migraciones e integridad financiera.
- Correccion aplicada: el checklist ahora verifica host/credenciales reales de correo, `DB_PERSISTENT=false`, usuario PostgreSQL distinto de `root` con clave y conserva los bloqueadores externos hasta contar con evidencia real.
- Riesgo: publicar sin recuperacion probada, aislamiento de privilegios, alertas o integracion bancaria certificada.
- Evidencia local 22 de septiembre de 2026: `php artisan app:production-readiness` ejecuto todos los controles y reporto 15 bloqueadores en el entorno local: entorno/HTTPS/cookies, Redis, correo real, respaldo restaurado, monitoreo, webhook, QR y usuario PostgreSQL limitado. `DB_PERSISTENT=false`, `ext-intl`, migraciones e integridad financiera ya pasan.
- Control de despliegue: [`deployment/scripts/deploy.sh`](deployment/scripts/deploy.sh) ahora recupera la aplicación si falla una etapa mientras está en mantenimiento.
- Accion: ejecutar el checklist en la infraestructura final y guardar evidencia de cada control; no usar el resultado local como aprobación de producción.
- Criterio de salida: `php artisan app:production-readiness` sin bloqueadores y con evidencia de restauracion reciente.

### P1-01. Exportaciones cargadas completas en memoria

- Estado: reducido. CSV usa `lazyById` por bloques y `streamDownload`; PDF síncrono rechaza más de 500 filas y registra duración, cantidad de filas y memoria usada.
- Validacion: [`ExportControllerTest.php`](tests/Unit/ExportControllerTest.php) verifica el límite de 500 filas; [`ExportController.php`](app/Http/Controllers/ExportController.php) registra `export.completed` para CSV y PDF.
- Impacto: picos de memoria, workers ocupados durante mucho tiempo y timeouts al exportar socios, facturas, empleados o medidores.
- Accion restante: evaluar PDF asincrónico si el negocio necesita superar 500 filas; usar CSV para exportaciones grandes.

### P1-02. Cache de datos financieros demasiado larga

- Estado: controlado en código. Las lecturas financieras, administrativas y de secretaria usan `OperationalCache` con TTL de 10 minutos y dominios `billing`, `operations` o `settings`; no quedan `addDays(7)` en `app`.
- Invalidacion: facturas, cobros, ordenes de pago, facturacion automatica, gastos, lecturas, tarifas, socios, empleados, operaciones de secretaria y configuración incrementan el dominio correspondiente.
- Impacto: un fallo de invalidacion puede mostrar saldos, conteos, ordenes o estadisticas antiguas durante dias.
- Accion operativa: ejecutar pruebas de integración contra PostgreSQL y medir hit/miss bajo carga; el API bancario consume lecturas frescas del dominio `billing` tras cada confirmación idempotente.

### P1-03. Complejidad excesiva y responsabilidades mezcladas

- Estado: reducido por primera entrega. QR de cobros vive en [`PaymentQrService.php`](app/Services/PaymentQrService.php) y las lecturas de caja en [`CashRegisterQuery.php`](app/Services/CashRegisterQuery.php); los controladores conservan rutas, validacion y transiciones.
- Evidencia actual de tamano:
  - [`TechnicalPanelService.php`](app/Services/TechnicalPanelService.php): 1.399 lineas.
  - [`CobroController.php`](app/Http/Controllers/CobroController.php): 767 lineas, con QR extraido.
  - [`PaymentOrderService.php`](app/Services/PaymentOrderService.php): 741 lineas.
  - [`BillingAutomationService.php`](app/Services/BillingAutomationService.php): 649 lineas.
  - [`ReportService.php`](app/Services/ReportService.php): 678 lineas.
  - [`SecretariaOperacionController.php`](app/Http/Controllers/SecretariaOperacionController.php): 503 lineas, con consultas de caja extraidas.
- Impacto: alto acoplamiento, pruebas grandes, cambios riesgosos y dificultad para aislar reglas contables.
- Accion restante: extraer por casos de uso `PaymentOrderService`, `BillingAutomationService` y `TechnicalPanelService`; separar catálogos, estadísticas, conciliación y reportes con pruebas de contrato.

### P1-04. Cobertura de autorizacion y procesos criticos incompleta

- Estado: parcial. Existen pruebas del middleware para invitado, acceso permitido y 403, una prueba de aislamiento/versionado de caché y flujos de cobro con caja abierta/cerrada.
- Evidencia pendiente: la matriz completa por rol, exportaciones, facturacion automatica, concurrencia de lecturas, reportes, anulaciones, cierres de caja y migraciones completas.
- Impacto: un cambio en permisos o reglas financieras puede romper el CRM sin señal automatica.
- Accion restante: ampliar la matriz a rutas feature de administrador, secretaria y tecnico; agregar concurrencia, idempotencia, exportaciones, reportes y transiciones de estado.

### P1-05. Facturacion automatica como operacion costosa de negocio

- Evidencia: [`BillingAutomationService.php`](app/Services/BillingAutomationService.php) concentra deteccion de periodos, lecturas, calculo y generacion de facturas. El proceso se ejecuta mediante el comando [`SyncCurrentInvoices.php`](app/Console/Commands/SyncCurrentInvoices.php), por lo que debe garantizarse que nunca se dispare desde una navegacion normal ni se duplique con scheduler.
- Impacto: locks, consultas y escrituras pueden competir con cobros y navegacion si se lanza fuera de una ventana controlada.
- Accion: mantenerlo exclusivamente como job/comando idempotente, medir duracion por lote y agregar prueba de doble ejecucion concurrente.

### P2-01. Dependencia JavaScript vulnerable transitoria

- Estado: resuelto. `npm audit fix` actualizo el lockfile y `npm audit --audit-level=moderate` termina con `0 vulnerabilities`.
- Evidencia historica: el aviso alto estaba en `nanoid`, dependencia transitoria.
- Impacto: riesgo en la cadena de build, aunque no se ha demostrado explotacion en runtime del CRM.
- Accion restante: mantener la auditoria en CI y revisar el diff del bundle en futuras actualizaciones.

### P2-02. Logout acepta GET y desactiva CSRF

- Estado: resuelto. [`routes/web.php`](routes/web.php) acepta solo `POST /logout` y conserva el middleware CSRF.
- Validacion: [`ExampleTest.php`](tests/Feature/ExampleTest.php) verifica que `GET /logout` responde 405 y que el POST cierra una sesion con token antiguo.
- Impacto: un enlace externo puede cerrar sesiones. No modifica datos, pero rompe la semantica HTTP y permite una denegacion sencilla de sesion.
- Accion restante: ninguna para este hallazgo.

### P2-03. Dos modelos para conceptos de rol

- Estado: minimo residual. El codigo productivo usa [`AccessRole.php`](app/Models/AccessRole.php) para `user_roles` y [`EmployeeRole.php`](app/Models/EmployeeRole.php) para `roles`; [`User.php`](app/Models/User.php), [`Permission.php`](app/Models/Permission.php), [`Empleado.php`](app/Models/Empleado.php) y el controlador de empleados ya no usan nombres ambiguos.
- Compatibilidad: [`Role.php`](app/Models/Role.php) y [`Rol.php`](app/Models/Rol.php) permanecen como wrappers deprecated para consumidores externos, sin cambiar tablas ni relaciones.
- Validacion: [`RoleModelNamingTest.php`](tests/Unit/RoleModelNamingTest.php) verifica tablas, clave primaria y compatibilidad legacy.
- Accion restante: retirar los wrappers en una version posterior, cuando no existan consumidores externos.

### P2-04. Acoplamiento fuerte a PostgreSQL

- Estado: minimo residual. PostgreSQL queda declarado como motor soportado por defecto en [`config/database.php`](config/database.php), las colas y `.env.example`; `.env.testing.example` conserva SQLite solo para pruebas aisladas.
- Evidencia: uso extendido de `ilike`, `FILTER`, `DISTINCT ON`, `INTERVAL`, `LPAD`, `hashtext`, locks advisory y casts PostgreSQL en controladores y migraciones.
- Impacto: SQLite u otros motores no representan el comportamiento real; las pruebas locales pueden ocultar diferencias de SQL y concurrencia.
- Accion restante: ejecutar CI contra PostgreSQL y encapsular SQL especifico donde sea razonable; no intentar soportar SQLite para el CRM completo.

### P2-08. Índices redundantes y migración de esquema pendiente

- Estado: resuelto para duplicados exactos revisados. La migración pendiente `2026_08_19_140000_fix_supabase_linter_warnings_and_performance_indexes` fue aplicada y las migraciones `2026_09_22_010000_consolidate_redundant_operational_indexes` y `2026_09_22_011000_consolidate_identity_meter_indexes` eliminaron índices superpuestos.
- Evidencia: se ejecutaron `EXPLAIN (ANALYZE, BUFFERS)` sobre facturas, cobros, lecturas, socios y órdenes de pago. Los planes observados quedaron por debajo de 2 ms de ejecución en los datos actuales; las lecturas secuenciales restantes corresponden a tablas pequeñas.
- Accion restante: repetir `pg_stat_user_indexes` y `EXPLAIN` después de una carga real del CRM en staging, antes de hacer otra depuración de índices por bajo uso.

### P2-05. Seguridad de archivos privados con compatibilidad legacy

- Evidencia: [`PrivateMedia.php`](app/Support/PrivateMedia.php) busca primero el disco local pero tambien sirve archivos desde el disco `public` como compatibilidad.
- Impacto: archivos legacy que permanezcan bajo `storage/app/public` dependen de una ruta publica existente, aunque la respuesta tenga cabeceras privadas.
- Accion: completar la migracion, verificar que no queden referencias publicas, eliminar el fallback al disco `public` cuando la auditoria de datos lo permita y probar acceso directo al symlink.

### P2-06. Defaults operativos permisivos (trasladado a P3-02)

- Estado: reclasificado. El riesgo operativo de defaults pertenece ahora a P3-02; `DB_PERSISTENT` usa `false` y el correo real se entrega exclusivamente mediante Brevo API.

### P2-07. Documentacion contradictoria y obsoleta

- Estado: resuelto como riesgo de consulta. Los documentos historicos ahora estan marcados como archivados y remiten a esta auditoria como backlog vigente.
- Evidencia historica:
  - [`AUDITORIA_PREPRODUCCION.md`](AUDITORIA_PREPRODUCCION.md) afirma cero vulnerabilidades, pero `composer audit` encuentra 10 avisos.
  - [`ANALISIS_RENDIMIENTO.md`](ANALISIS_RENDIMIENTO.md) conserva tareas de indices, paginacion y portal que ya fueron implementadas o retiradas.
  - [`ANALISIS_REFACTORIZACION_CARGA.md`](ANALISIS_REFACTORIZACION_CARGA.md) referencia `ClientePortalController`, eliminado al pasar el producto a CRM.
- Impacto historico: se priorizaban tareas ya resueltas y se perdia confianza en los criterios de lanzamiento.
- Accion restante: mantener esta auditoria como backlog vigente y fechar cualquier nuevo analisis.

### P3-01. Portabilidad y mantenibilidad del frontend

- Evidencia: el frontend depende de Vite, Tailwind, Chart.js, Leaflet y Swiper; el build actual funciona, pero no existe una prueba automatica equivalente de tamano de bundle, presupuesto de rendimiento o regresion visual para el CRM despues de retirar el portal.
- Impacto: el bundle puede crecer sin alerta y una vista administrativa puede degradarse sin fallar tests PHP.
- Accion: establecer limites de bundle, ejecutar build en CI, medir rutas administrativas representativas y conservar una auditoria visual reducida del CRM.

### P3-02. Defaults operativos permisivos

- Estado: parcial. PostgreSQL y `DB_PERSISTENT=false` son los defaults documentados; el correo real usa Brevo API y el checklist de producción rechaza una clave API o remitente incompletos.
- Evidencia: [`config/database.php`](config/database.php), [`config/mail.php`](config/mail.php), [`.env.example`](.env.example) y [`.env.production.example`](.env.production.example).
- Accion restante: hacer que el arranque de producción falle inmediatamente ante variables obligatorias ausentes, además del bloqueo actual de `app:production-readiness`.

## Deuda descartada o ya resuelta

No debe volver a registrarse como deuda vigente sin nueva evidencia:

- Portal web publico de usuarios: retirado; sus rutas, vistas, controlador, middleware y prueba dedicada ya no forman parte del producto.
- Indices basicos, paginacion principal y eager loading: existen implementaciones y migraciones; requieren medicion, no una reapertura generica.
- Build frontend: `npm run build` funciona.
- Suite actual: 47 pruebas y 120 aserciones pasan.
- Webhook bancario: tiene firma, ventana anti-repeticion e idempotencia probadas.
- Integridad financiera: tiene auditoria y restricciones de base de datos; falta ampliar casos, no rehacerla desde cero.

## Orden recomendado de reduccion

1. Ejecutar el checklist de produccion en staging equivalente y cerrar los bloqueadores reales.
2. Corregir exportaciones para streaming/chunks.
3. Reducir y centralizar cache financiera; agregar pruebas de invalidacion.
4. Construir matriz de autorizacion y pruebas de procesos criticos.
5. Dividir `TechnicalPanelService`, `CobroController`, `PaymentOrderService` y `BillingAutomationService` por caso de uso.
6. Retirar los wrappers legacy de roles cuando se confirme que no quedan consumidores externos.
7. Ejecutar CI con PostgreSQL, auditorias de dependencias y presupuesto frontend.

## Comandos de control

```powershell
composer audit --format=plain
npm audit --audit-level=moderate
php artisan test
php artisan app:production-readiness
npm run build
```
