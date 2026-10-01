# Analisis de refactorizacion, bad smells y carga

> Documento historico archivado. El portal publico y las referencias a
> `ClientePortalController` ya no forman parte del producto actual.

Fecha: 8 de junio de 2026  
Proyecto: EPSAS Laravel / Blade

## Resumen ejecutivo

El sistema no puede considerarse apto para soportar 5000 usuarios con la evidencia actual. La prueba local de humo con solo 50 usuarios virtuales sobre `/login` ya produjo 84.96% de fallos por timeout usando el servidor de desarrollo de Laravel. Este resultado no certifica produccion, porque `php artisan serve` no es un servidor real de alto trafico, pero si confirma que el ambiente actual no sirve para validar 5000 usuarios y que se necesita un staging con PHP-FPM/Nginx o equivalente.

El cuello de botella mas probable esta en backend/base de datos: sincronizacion automatica de facturas en rutas GET, consultas grandes, catologos completos en memoria, polling automatico de notificaciones y dependencia de Supabase para pantallas administrativas.

## Refactorizacion aplicada como prueba

Archivo modificado: `app/Http/Controllers/FacturaController.php`

Cambio:
- Se centralizo la lista de relaciones de factura en `INVOICE_DETAIL_RELATIONS`.
- Se extrajo `loadInvoiceDetail()` para evitar repetir `load([...])`.
- Se extrajo `invoiceViewData()` para reutilizar datos comunes de show/print/pdf/email.
- Se extrajo `invoicePaymentSummary()` para calcular subtotal, pagado y pendiente en un solo lugar.
- `show()`, `sendEmail()`, `pdf()` y `print()` conservan la misma salida esperada, pero con menos duplicacion.

Validacion realizada:
- `php -l app\Http\Controllers\FacturaController.php`: sin errores de sintaxis.
- `vendor\bin\pint.bat app\Http\Controllers\FacturaController.php`: formato aplicado.
- `npm.cmd run build`: build correcto.
- `php artisan test --filter=ExampleTest`: 2 tests pasados.

## Bad smells identificados

1. Controladores demasiado grandes
   - `app/Http/Controllers/TecnicoPanelController.php`: 1103 lineas, 19 metodos publicos y 27 privados.
   - `app/Http/Controllers/ReporteController.php`: 621 lineas.
   - `app/Http/Controllers/ClientePortalController.php`: 588 lineas.
   - `app/Http/Controllers/FacturaController.php`: 548 lineas antes del refactor.

2. Sincronizacion pesada dentro de rutas GET
   - `FacturaController::index()` llama `ensureCurrentInvoices()`.
   - `CobroController::index()` llama `ensureCurrentInvoices()`.
   - Esto convierte una navegacion normal en una operacion potencialmente costosa de negocio/base de datos.

3. Servicio de facturacion con demasiadas responsabilidades
   - `app/Services/BillingAutomationService.php` mezcla verificacion de vigencia, sincronizacion, busqueda de lecturas, generacion de periodos, calculo de saldos e insercion de facturas.
   - Recomendacion: separarlo en servicios mas pequenos: detector de pendientes, generador de facturas, repositorio de contexto y tarea programada.

4. Carga de catalogos completos en memoria
   - `TecnicoPanelController::socioCatalog()`.
   - `TecnicoPanelController::optimizedConsumptionCatalog()`.
   - `FacturaController::billingCandidates()`.
   - Para 5000 usuarios concurrentes esto puede multiplicar memoria y tiempo de respuesta.

5. Exportaciones sin limite
   - `ExportController` usa `get()` para empleados, socios, tarifas, medidores y facturas.
   - Riesgo: timeouts y alto consumo de memoria si crece la base.

6. Polling de notificaciones en cada pantalla
   - `resources/views/partials/header-with-notifications.blade.php` ejecuta `load()` al cargar y luego `setInterval(..., 45000)`.
   - Esto agrega trafico constante a la API y a la base aunque el usuario no abra la campanita.

7. Cache manual de larga duracion
   - Varias pantallas usan `Cache::remember(..., now()->addDays(7))`.
   - El rendimiento mejora, pero aumenta el riesgo de datos desactualizados si se omite alguna invalidacion.

8. Codigo muerto
   - `ClientePortalController::portalPages()` retorna `portalSettings()['pages']` y deja debajo un bloque grande inalcanzable.
   - Recomendacion: mover ese fallback a `PortalContent` o eliminarlo si ya no aplica.

9. Consultas muy acopladas a PostgreSQL
   - Uso frecuente de `ilike`, `FILTER`, `CREATE INDEX`, vistas y expresiones Postgres.
   - Esta bien si produccion es Postgres, pero dificulta pruebas locales con SQLite.

10. Configuracion local no apta para rendimiento
   - `php artisan about` mostro Debug Mode habilitado, rutas/config no cacheadas.
   - Para pruebas reales se necesita `APP_DEBUG=false`, `php artisan config:cache`, `route:cache`, `view:cache`, OPcache activo y servidor real.

## Prueba de carga agregada

Archivo agregado: `scripts/load-test-5000-users.mjs`

Caracteristicas:
- No requiere paquetes nuevos.
- Usa HTTP nativo de Node.
- Simula usuarios virtuales con ramp-up, duracion sostenida, tiempo de pensamiento y timeout.
- Reporta requests, throughput, fallos, codigos HTTP, errores, p50, p90, p95, p99 y concurrencia maxima.

Comando de humo ejecutado:

```powershell
node scripts\load-test-5000-users.mjs --url=http://127.0.0.1:8010 --paths=/login --users=50 --ramp=2s --duration=8s --think=100-300 --timeout=5s --max-failure-rate=5
```

Resultado:

| Metrica | Resultado |
| --- | ---: |
| Usuarios virtuales | 50 |
| Requests totales | 113 |
| OK | 17 |
| Fallidos | 96 |
| Fallos | 84.96% |
| Throughput | 7.6 req/s |
| Max in-flight | 50 |
| Latencia promedio | 2643.5 ms |
| p95 | 4891.55 ms |
| Error principal | AbortError |

Conclusion de la prueba local: con el servidor de desarrollo, el sistema falla mucho antes de 5000 usuarios. No se debe usar este resultado como capacidad final de produccion, pero si como evidencia de que hace falta un ambiente real de prueba.

## Comando para prueba completa de 5000 usuarios

Ejecutar solo contra staging o produccion controlada, no contra `php artisan serve`:

```powershell
node scripts\load-test-5000-users.mjs --url=https://TU-STAGING-O-DOMINIO --users=5000 --ramp=5m --duration=15m --think=1000-3000 --timeout=10s --paths=/login,/portal/cliente,/portal/cliente/horarios,/portal/cliente/contactanos --max-failure-rate=1
```

Para rutas autenticadas se puede pasar una cookie de sesion de prueba:

```powershell
node scripts\load-test-5000-users.mjs --url=https://TU-STAGING-O-DOMINIO --users=5000 --ramp=5m --duration=15m --think=1000-3000 --timeout=10s --paths=/dashboard,/admin/facturas,/admin/cobros --cookie="NOMBRE_COOKIE=VALOR" --max-failure-rate=1
```

## Criterios minimos para declarar apto

- Fallos totales menores a 1%.
- Errores 5xx igual a 0 o casi 0.
- p95 publico menor a 800 ms.
- p95 administrativo menor a 1500 ms.
- CPU sostenida menor a 75%.
- Memoria sin crecimiento continuo.
- Pool de base de datos sin agotarse.
- Sin timeouts de Supabase.
- Sin colas acumuladas en operaciones de facturacion, correo o PDF.

## Recomendaciones antes del test real

1. Verificado en la arquitectura actual: `ensureCurrentInvoices()` ya no se invoca desde rutas web; se ejecuta mediante `billing:sync-current-invoices` programado cada 15 minutos con `withoutOverlapping`.
2. Cambiar catalogos completos por busqueda remota/paginada.
3. Cargar notificaciones solo al abrir la campanita o reducir frecuencia por rol.
4. Hacer exports por chunks/streaming.
5. Separar `BillingAutomationService` en servicios mas pequenos.
6. Revisar indices aplicados en Supabase con `EXPLAIN ANALYZE`.
7. Ejecutar el test real del CRM en staging con servidor equivalente a producción; las rutas `/portal/cliente*` de los comandos anteriores ya no existen.
