# ---- Stage 1: PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ---- Stage 2: PHP application ----
# No asset-build stage here, unlike GreenStock — this is an API-only app (no Blade/Vite/Ziggy),
# so there's nothing for Node to build.
FROM php:8.2-cli-alpine AS app

RUN apk add --no-cache postgresql-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql mbstring bcmath \
    && apk del $PHPIZE_DEPS

WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN php artisan package:discover --ansi \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
