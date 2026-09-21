#!/bin/bash
set -e

cp .env.example .env

# Parse DB_URL/DATABASE_URL into individual Laravel DB vars using PHP
php parse_db_url.php

# Overlay env vars from Render (including individual DB_* from linked database)
sed -i "s|^APP_ENV=.*|APP_ENV=${APP_ENV:-production}|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|" .env
sed -i "s|^APP_URL=.*|APP_URL=${APP_URL:-}|" .env
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY:-}|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION:-pgsql}|" .env

# Individual DB vars from Render's linked PostgreSQL
[ -n "$DB_HOST" ] && sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env
[ -n "$DB_PORT" ] && sed -i "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" .env
[ -n "$DB_DATABASE" ] && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
[ -n "$DB_USERNAME" ] && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
[ -n "$DB_PASSWORD" ] && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
[ -n "$DB_SSLMODE" ] && sed -i "s|^DB_SSLMODE=.*|DB_SSLMODE=$DB_SSLMODE|" .env

sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER:-database}|" .env
sed -i "s|^CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE:-database}|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}|" .env
sed -i "s|^FRONTEND_URL=.*|FRONTEND_URL=${FRONTEND_URL:-}|" .env
sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-}|" .env
sed -i "s|^CHAPA_MODE=.*|CHAPA_MODE=${CHAPA_MODE:-test}|" .env
sed -i "s|^CHAPA_SECRET_KEY=.*|CHAPA_SECRET_KEY=${CHAPA_SECRET_KEY:-}|" .env
sed -i "s|^CHAPA_CALLBACK_URL=.*|CHAPA_CALLBACK_URL=${CHAPA_CALLBACK_URL:-}|" .env
sed -i "s|^CHAPA_RETURN_URL=.*|CHAPA_RETURN_URL=${CHAPA_RETURN_URL:-}|" .env
sed -i "s|^CHAPA_WEBHOOK_URL=.*|CHAPA_WEBHOOK_URL=${CHAPA_WEBHOOK_URL:-}|" .env
sed -i "s|^CHAPA_WEBHOOK_SECRET=.*|CHAPA_WEBHOOK_SECRET=${CHAPA_WEBHOOK_SECRET:-}|" .env
sed -i "s|^CLOUDINARY_CLOUD_NAME=.*|CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME:-}|" .env
sed -i "s|^CLOUDINARY_KEY=.*|CLOUDINARY_KEY=${CLOUDINARY_KEY:-}|" .env
sed -i "s|^CLOUDINARY_SECRET=.*|CLOUDINARY_SECRET=${CLOUDINARY_SECRET:-}|" .env

php artisan key:generate --force
php artisan migrate:fresh --force --seed
php artisan config:cache
php artisan route:cache

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
