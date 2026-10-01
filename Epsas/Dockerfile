FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.3-fpm-bookworm AS runtime
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" intl opcache pdo_pgsql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/epsas-entrypoint
RUN chmod +x /usr/local/bin/epsas-entrypoint

USER www-data
ENTRYPOINT ["epsas-entrypoint"]
CMD ["php-fpm", "-F"]
