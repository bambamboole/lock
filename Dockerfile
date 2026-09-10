# syntax=docker/dockerfile:1
# check=skip=SecretsUsedInArgOrEnv

ARG PHP_VERSION=8.5
ARG NODE_VERSION=22

# ---------------------------------------------------------------------------
# base: FrankenPHP (Caddy + PHP in one binary) with the extensions the app
# needs. Classic mode, no Octane: the request lifecycle is unchanged from
# php-fpm, so nothing in the app has to be worker-safe.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php${PHP_VERSION}-bookworm AS base

RUN install-php-extensions \
        bcmath \
        gmp \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        redis \
        zip

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && useradd --uid 1000 --user-group --create-home --shell /bin/bash app \
    && chown -R app:app /data /config

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

WORKDIR /app

# ---------------------------------------------------------------------------
# vendor: production composer dependencies plus the optimized autoloader and
# the package manifest. Scripts are skipped so the manifest is generated once,
# explicitly, after the full source is present.
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --no-progress --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi --no-interaction

# ---------------------------------------------------------------------------
# assets: the Vite build. It needs vendor/: the Lattice Vite plugin discovers
# component packages from vendor/composer/installed.json.
#
# `npm install`, not `npm ci`: the committed lockfile is generated on macOS and
# lacks the linux-only optional packages, which `npm ci` rejects as out of
# sync (see .github/actions/setup-test-env).
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-bookworm-slim AS assets

ARG VITE_APP_NAME=Lock

WORKDIR /app

COPY package.json package-lock.json .npmrc ./
RUN --mount=type=cache,target=/root/.npm \
    npm install --no-audit --no-fund

COPY --from=vendor /app/vendor ./vendor
COPY vite.config.ts tsconfig.json ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------------------------------------------------------------------------
# app: the runtime image. Everything is owned by the unprivileged `app` user;
# only storage/ and bootstrap/cache/ are written to at runtime.
# ---------------------------------------------------------------------------
FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

COPY --from=vendor --chown=app:app /app /app
COPY --from=assets --chown=app:app /app/public/build /app/public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chown -R app:app /app/storage /app/bootstrap/cache

USER app

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl --fail --silent "http://localhost:${PORT}/up" || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["app"]
