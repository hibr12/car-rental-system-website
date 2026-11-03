#!/usr/bin/env bash
set -euo pipefail

# Render can generate a persistent opaque secret, but Laravel needs a 32-byte
# encryption key. Derive the same valid key on every restart when APP_KEY was
# not already configured on an existing service.
if [ -z "${APP_KEY:-}" ]; then
    if [ -z "${APP_KEY_SEED:-}" ]; then
        echo "APP_KEY or APP_KEY_SEED must be configured." >&2
        exit 1
    fi

    APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY_SEED"), true));')"
    export APP_KEY
fi

if [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]; then
    export APP_URL="${APP_URL:-https://${RENDER_EXTERNAL_HOSTNAME}}"
    export FRONTEND_URL="${FRONTEND_URL:-${APP_URL}}"
    export SANCTUM_STATEFUL_DOMAINS="${SANCTUM_STATEFUL_DOMAINS:-${RENDER_EXTERNAL_HOSTNAME}}"
fi

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction

migration_attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$migration_attempt" -ge 30 ]; then
        echo "Database migration failed after ${migration_attempt} attempts." >&2
        exit 1
    fi

    echo "Database is not ready; retrying migration (${migration_attempt}/30)..." >&2
    migration_attempt=$((migration_attempt + 1))
    sleep 2
done

php artisan app:ensure-initial-admin --no-interaction
php artisan storage:link --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Reconcile stale bookings and payments without requiring a separate Render
# cron service. Queued notifications use the sync driver in this deployment.
php artisan schedule:work --no-interaction &

exec php artisan serve \
    --no-reload \
    --host=0.0.0.0 \
    --port="${PORT:-10000}"
