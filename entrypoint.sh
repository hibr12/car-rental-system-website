#!/bin/bash
set -e

cp .env.example .env

# Parse DB_URL/DATABASE_URL into individual Laravel DB vars using PHP
php parse_db_url.php

# Overlay ALL env vars from Render using PHP (replaces fragile sed)
php overlay_env.php

php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
