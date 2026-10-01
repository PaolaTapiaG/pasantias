# Prueba de carga de 1000 usuarios

> Evidencia historica archivada. El portal publico usado en esta prueba fue
> retirado; sus resultados no validan el CRM actual ni la infraestructura de
> produccion.

Fecha de ultima ejecucion: 10 de junio de 2026

## Resultado final

El portal publico supero la prueba local de 1000 usuarios virtuales navegando por las rutas que existian en junio de 2026:

- `/portal/cliente`
- `/portal/cliente/horarios`
- `/portal/cliente/contactanos`

Configuracion de la prueba:

- Rampa: 30 segundos
- Carga sostenida: 30 segundos
- Pausa por usuario: entre 5 y 10 segundos
- Tiempo maximo permitido por solicitud: 15 segundos
- IP virtual unica por usuario

Resultados:

| Indicador | Resultado |
| --- | ---: |
| Solicitudes totales | 6.471 |
| Solicitudes correctas | 6.471 |
| Errores | 0 |
| Tasa de error | 0% |
| Rendimiento | 92,57 solicitudes/segundo |
| Tiempo promedio | 34,19 ms |
| Tiempo p95 | 100,62 ms |
| Tiempo p99 | 130,82 ms |
| Tiempo maximo | 209,24 ms |

## Problemas encontrados y corregidos

1. El servidor de desarrollo de Laravel no era apto para pruebas concurrentes.
2. El cache y las sesiones en archivos se saturaban bajo carga.
3. Las paginas publicas estaticas creaban sesiones, cookies y limites de pago innecesarios.
4. Predis abria y cerraba conexiones Redis por solicitud, agotando los puertos temporales de Windows.
5. Apache mantenia trabajadores esperando conexiones inactivas durante demasiado tiempo.

Correcciones aplicadas:

- Entorno local de staging con Apache, OPcache y Redis.
- Cache, sesiones y colas configurados para Redis en staging.
- Conexiones Redis persistentes para evitar agotamiento de puertos.
- Cache de respuesta para paginas publicas estaticas.
- Paginas estaticas separadas de los flujos con sesion, formularios y pagos.
- Busquedas, facturas, pagos y comprobantes conservan sus controles de sesion, CSRF y limite de solicitudes.
- Prueba por rampas de 100, 500 y 1000 usuarios.

## Evolucion observada

Antes de los ajustes, la prueba de 1000 usuarios sobre el portal tuvo 22,87% de errores. Tras separar las paginas estaticas y reutilizar conexiones Redis, dos ejecuciones finales consecutivas terminaron con 0% de errores.

## Alcance y limites

Este resultado valida la navegacion publica de lectura en el entorno local de staging. No certifica todavia 1000 operaciones simultaneas de escritura, pagos, consultas de deuda autenticadas o tareas administrativas.

Antes de produccion se recomienda repetir la prueba en Linux con Nginx y PHP-FPM, Redis administrado, trabajadores de cola activos, monitoreo y el pool de conexiones de la base de datos configurado.

Tambien se ejecuto una rafaga extrema sin pausas sobre rutas de pago. El limite antiabuso respondio con codigos `429` y el staging de Windows agoto hilos y conexiones. Esta prueba confirma que los limites funcionan, pero tambien que Windows/Apache local no debe utilizarse como infraestructura final.

## Siguiente prueba requerida para el CRM

Ejecutar contra staging equivalente a producción, con una sesión de prueba y rutas actuales como `/login`, `/dashboard`, `/admin/facturas`, `/admin/cobros` y `/admin/ordenes-pago`. Separar lecturas autenticadas de escrituras, porque estas últimas modifican datos y requieren fixtures aislados.

## Comando histórico reproducible

```powershell
node scripts\load-test-5000-users.mjs --url=http://127.0.0.1:8080 --users=1000 --ramp=30s --duration=30s --think=5000-10000 --timeout=15s --paths=/portal/cliente,/portal/cliente/horarios,/portal/cliente/contactanos --unique-client-ips=true --max-failure-rate=1
```
