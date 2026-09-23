#!/bin/bash
set -e

cp .env.example .env

# Parse DB_URL/DATABASE_URL into individual Laravel DB vars using PHP
php parse_db_url.php

# Overlay ALL env vars from Render using PHP (replaces fragile sed)
php overlay_env.php

# Only mint a new key when one isn't already set — generating a fresh key on
# every boot would invalidate all existing sessions/cookies on every restart.
if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Periodic cleanup (stale payment/booking reconciliation) — no Render cron add-on.
php artisan schedule:work &

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
