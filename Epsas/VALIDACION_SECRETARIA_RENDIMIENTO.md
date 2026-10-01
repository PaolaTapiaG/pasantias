# Validacion de secretaria y rendimiento

> Documento historico archivado. Las métricas corresponden al staging local de
> junio de 2026 y la carga referida al portal público retirado. No constituyen
> validación del CRM actual en producción.

Fecha: 10 de junio de 2026

## Funciones agregadas para secretaria

- Apertura y cierre diario de caja.
- Registro de solicitudes, reclamos y atencion al socio.
- Derivacion de solicitudes a tecnicos con orden tecnica.
- Seguimiento del estado de solicitudes.
- Preparacion de comunicados para aprobacion del administrador.
- Cobros, facturas, revision QR, socios, reportes y carnetizacion integrados con los flujos existentes.
- El administrador puede revisar cajas y aprobar, rechazar o archivar comunicados.
- Secretaria no puede realizar revisiones administrativas criticas.
- El tecnico recibe acceso denegado al modulo de operaciones de secretaria.

## Correcciones de rendimiento

- Sesiones Redis separadas de la conexion persistente usada por cache.
- Login sin consultas a la base de datos durante la autenticacion habitual.
- Preparacion anticipada de usuarios, roles, paneles y listados frecuentes.
- Cache con invalidacion para cobros, facturas, ordenes QR, caja y solicitudes.
- Eliminacion de consultas directas escondidas en vistas.
- Medidor local protegido para separar tiempo de aplicacion y base de datos.

## Resultado autenticado por rol

La medicion se ejecuto en paralelo contra `http://127.0.0.1:8080`, con Redis, Apache y OPcache.

| Rol | Login | Pantallas principales | Consultas BD durante navegacion |
| --- | ---: | ---: | ---: |
| Administrador | 588 ms | 31 a 64 ms | 0 |
| Secretaria | 516 ms | 38 a 80 ms | 0 |
| Tecnico | 461 ms | 31 a 67 ms | 0 |

El login ya no tarda 5 a 6 segundos. Su tiempo restante corresponde principalmente a la verificacion segura de la contrasena con bcrypt, no a consultas de base de datos.

## Prueba de 1000 usuarios

- 1000 usuarios virtuales.
- 6486 solicitudes correctas.
- 0 errores.
- 0% de fallos.
- Promedio: 28,53 ms.
- p95: 92,41 ms.
- p99: 144,67 ms.
- Maximo: 278,60 ms.

La prueba de 1000 usuarios valida únicamente navegación pública histórica. Los flujos autenticados y de escritura del CRM deben probarse por separado con datos de prueba para evitar registrar cobros, facturas o cambios reales.

## Comandos de verificacion

```powershell
php artisan app:performance-warm
node scripts\benchmark-auth-routes.mjs --profile=true --url=http://127.0.0.1:8080 --login=USUARIO --password=CLAVE --paths=/dashboard
node scripts\load-test-5000-users.mjs --url=http://127.0.0.1:8080 --users=1000 --ramp=30s --duration=30s --think=5000-10000 --timeout=15s --paths=/portal/cliente,/portal/cliente/horarios,/portal/cliente/contactanos --unique-client-ips=true --max-failure-rate=1
```
