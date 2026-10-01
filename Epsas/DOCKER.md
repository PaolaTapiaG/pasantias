# EPSAS con Docker

## Requisitos

- Docker Desktop actualizado y ejecutandose.
- Copiar `.env.docker.example` como `.env.docker`.

## Primer arranque

Desde `Epsas/`:

```powershell
Copy-Item .env.docker.example .env.docker
docker compose build
 docker compose run --rm app php artisan key:generate --show
```

Copiar la clave mostrada en `APP_KEY` dentro de `.env.docker` y ejecutar:

```powershell
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize
```

Abrir http://localhost:8080. La comprobacion de salud de Laravel queda disponible en `/up`.

## Operacion

```powershell
docker compose ps
docker compose logs -f app worker scheduler
docker compose exec app php artisan tinker
docker compose down
```

`postgres_data`, `redis_data` y `epsas_storage` son volumenes persistentes. `docker compose down -v` elimina tambien esos datos y no debe usarse en un entorno con informacion real.

## Produccion

Este compose sirve para desarrollo avanzado o staging. Antes de produccion se deben cambiar las credenciales, usar TLS delante de Nginx, extraer secretos del archivo `.env.docker`, respaldar los volumenes y decidir si PostgreSQL sera administrado externamente. No se debe reutilizar la contrasena de ejemplo.
