# syntax=docker/dockerfile:1

FROM serversideup/php:8.5-frankenphp AS base

USER root
RUN install-php-extensions bcmath gmp intl

FROM base AS vendor

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --no-progress --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --no-interaction

# The Lattice Vite plugin discovers component packages from vendor/composer/installed.json.
# `npm install`, not `npm ci`: the lockfile is generated on macOS and lacks the linux-only
# optional packages, which `npm ci` rejects as out of sync.
FROM node:24-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm install --no-audit --no-fund

COPY --from=vendor /var/www/html/vendor ./vendor
COPY vite.config.ts tsconfig.json ./
COPY resources ./resources
COPY public ./public
RUN VITE_APP_NAME=Lock npm run build

FROM base AS app

# Every container start runs `php artisan optimize`; migrations stay with `php artisan app:deploy`,
# which also runs the data migrations and which the hook below runs when AUTORUN_APP_DEPLOY=true.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_APP_DEPLOY=false \
    HEALTHCHECK_PATH=/up \
    OCTANE_SERVER=frankenphp \
    PHP_OPCACHE_ENABLE=1 \
    APP_NAME=Lock \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_SECURE_COOKIE=true \
    OIDC_FIRST_PARTY_TRUSTED=true

COPY --chmod=755 <<'EOF' /etc/entrypoint.d/60-app-deploy.sh
#!/bin/sh
if [ "$AUTORUN_APP_DEPLOY" = "true" ]; then
    echo "🚀 Running the release step: \"php artisan app:deploy\"..."
    php "$APP_BASE_DIR/artisan" app:deploy --no-interaction
fi
EOF

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

USER www-data

CMD ["php", "artisan", "octane:start", "--host=0.0.0.0", "--port=8080"]
