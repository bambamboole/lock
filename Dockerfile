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
FROM node:22-bookworm-slim AS assets

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

# Every container start runs `php artisan optimize`; migrations stay with the explicit
# `php artisan app:deploy` release step, which also runs the data migrations.
# The image's Caddyfile answers 403 for every dotted path segment except a root-level
# /.well-known/, which would block each realm's discovery document and JWKS. Routing them
# to index.php up front skips that rule; Laravel still sees the original request URI.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    CADDY_SERVER_EXTRA_DIRECTIVES="rewrite /realms/*/.well-known/* /index.php" \
    HEALTHCHECK_PATH=/up \
    PHP_OPCACHE_ENABLE=1 \
    APP_NAME=Lock \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_SECURE_COOKIE=true \
    OIDC_FIRST_PARTY_TRUSTED=true

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

USER www-data
