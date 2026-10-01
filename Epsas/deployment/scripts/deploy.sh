#!/usr/bin/env bash
set -euo pipefail

cd /var/www/epsas/current

maintenance_enabled=false
restore_service() {
	if [ "$maintenance_enabled" = true ]; then
		php artisan up || true
	fi
}
trap restore_service EXIT

php artisan down --retry=60
maintenance_enabled=true
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
rm -f public/hot
php artisan migrate --force
php artisan optimize
php artisan app:performance-warm
php artisan app:production-readiness
sudo systemctl restart php8.3-fpm epsas-worker epsas-scheduler
php artisan up
maintenance_enabled=false
