#!/bin/bash
set -e

cp .env.example .env

# Parse DB_URL into individual Laravel DB vars using PHP
php parse_db_url.php

# Overlay env vars from Render
sed -i "s|^APP_ENV=.*|APP_ENV=${APP_ENV:-production}|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|" .env
sed -i "s|^APP_URL=.*|APP_URL=${APP_URL:-}|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION:-pgsql}|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER:-database}|" .env
sed -i "s|^CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE:-database}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}|" .env
sed -i "s|^FRONTEND_URL=.*|FRONTEND_URL=${FRONTEND_URL:-*}|" .env
sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-*}|" .env
sed -i "s|^CHAPA_MODE=.*|CHAPA_MODE=${CHAPA_MODE:-test}|" .env
sed -i "s|^CHAPA_SECRET_KEY=.*|CHAPA_SECRET_KEY=${CHAPA_SECRET_KEY:-}|" .env
sed -i "s|^CHAPA_CALLBACK_URL=.*|CHAPA_CALLBACK_URL=${CHAPA_CALLBACK_URL:-}|" .env
sed -i "s|^CHAPA_RETURN_URL=.*|CHAPA_RETURN_URL=${CHAPA_RETURN_URL:-}|" .env
sed -i "s|^CHAPA_WEBHOOK_URL=.*|CHAPA_WEBHOOK_URL=${CHAPA_WEBHOOK_URL:-}|" .env
sed -i "s|^CLOUDINARY_CLOUD_NAME=.*|CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME:-}|" .env
sed -i "s|^CLOUDINARY_KEY=.*|CLOUDINARY_KEY=${CLOUDINARY_KEY:-}|" .env
sed -i "s|^CLOUDINARY_SECRET=.*|CLOUDINARY_SECRET=${CLOUDINARY_SECRET:-}|" .env

php artisan key:generate --force
php artisan migrate:fresh --force
php artisan config:cache
php artisan route:cache

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
