# Despliegue de produccion EPSAS

## Requisitos

- Linux, Nginx, PHP-FPM 8.3+, PostgreSQL y Redis.
- Extension PHP `intl` instalada y habilitada para CLI y PHP-FPM.
- Dominio y certificado TLS valido.
- Usuario PostgreSQL de aplicacion sin privilegios administrativos.
- Trabajador de colas y scheduler supervisados.
- Almacenamiento privado persistente y respaldado.

## Cierre de bloqueadores

1. Copiar `.env.production.example` a `.env` en el servidor y completar secretos reales.
2. Adaptar `nginx/epsas.conf` al dominio y activar certificado TLS.
3. Ejecutar `postgres/provision-app-role.sql` como administrador y usar `epsas_app` en la aplicacion.
4. Instalar y habilitar los servicios de `systemd`.
5. Configurar un monitor externo para `GET /health/ready` enviando `X-Health-Token`, y marcar `EXTERNAL_MONITOR_CONFIGURED=true` solo despues de comprobar alertas reales.
6. Programar `scripts/backup-postgres.sh`, restaurar periodicamente en una base aislada distinta a `PRODUCTION_DATABASE_NAME` y ejecutar `verify-restore.sh`.
7. Configurar el banco para firmar webhooks con HMAC SHA-256 sobre `timestamp.cuerpo` hacia `POST /webhooks/bank/payments`, y asignar un empleado de sistema exclusivo en `BANK_WEBHOOK_EMPLOYEE_ID`.
8. Configurar `payment_static_qr_payload` desde Administracion solo con el identificador oficial entregado por banco o pasarela. Si esta vacio, el CRM no genera nuevas ordenes QR.
9. Ejecutar `php artisan app:production-readiness`; el despliegue no debe continuar si devuelve error.

## Valores criticos de entorno

- `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_COOKIE=__Host-epsas-session`, `SESSION_PATH=/` y `SESSION_DOMAIN=` vacio.
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis` y `QUEUE_CONNECTION=redis`.
- `PRODUCTION_MONITORING_ENABLED=true`, `MONITORING_HEALTH_TOKEN` con 32+ caracteres aleatorios y `EXTERNAL_MONITOR_CONFIGURED=true` solo despues de validar alertas reales.
- `APP_URL` debe comenzar con `https://` y `ENFORCE_HTTPS=true`.
- El CRM debe servir los documentos y archivos operativos desde almacenamiento privado; no depender de rutas publicas para comprobantes, evidencias o fotografias.

La evidencia de restauracion incluye hash SHA-256 del archivo restaurado, base aislada, fecha y auditoria de integridad financiera. No se acepta registrar una fecha manual en el entorno.

El webhook bancario espera las cabeceras `X-EPSAS-Timestamp` y `X-EPSAS-Signature`, y un JSON con `event_id`, `event_type=payment.confirmed`, `order_code`, `reference`, `amount`, `currency=BOB` y `paid_at`.
