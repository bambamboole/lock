#!/bin/sh
set -eu

cd /app

case "${1:-app}" in
    app|queue|scheduler|deploy)
        mode="$1"
        shift
        ;;
    *)
        mode="run"
        ;;
esac

# Bytecode caches are per container and depend on the runtime environment, so
# they are built on every start: config, events, routes, views, and the Lattice
# discovery manifest (registered through the `optimize` hook).
php artisan optimize --no-interaction --quiet

case "$mode" in
    app)
        exec frankenphp run --config /etc/frankenphp/Caddyfile
        ;;
    queue)
        exec php artisan queue:work --no-interaction --tries=3 --max-time=3600 "$@"
        ;;
    scheduler)
        exec php artisan schedule:work --no-interaction "$@"
        ;;
    deploy)
        exec php artisan app:deploy --no-interaction "$@"
        ;;
    run)
        exec "$@"
        ;;
esac
