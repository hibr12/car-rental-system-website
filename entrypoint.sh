#!/bin/bash

cp .env.example .env

sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY:-}|g" .env
sed -i "s|APP_ENV=.*|APP_ENV=${APP_ENV:-production}|g" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|g" .env
sed -i "s|APP_URL=.*|APP_URL=${APP_URL:-}|g" .env
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=${DB_CONNECTION:-pgsql}|g" .env
sed -i "s|DB_URL=.*|DB_URL=${DB_URL:-}|g" .env
sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=${SESSION_DRIVER:-database}|g" .env
sed -i "s|CACHE_STORE=.*|CACHE_STORE=${CACHE_STORE:-database}|g" .env
sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}|g" .env
sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=${FRONTEND_URL:-*}|g" .env
sed -i "s|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-*}|g" .env
sed -i "s|CHAPA_MODE=.*|CHAPA_MODE=${CHAPA_MODE:-test}|g" .env
sed -i "s|CHAPA_SECRET_KEY=.*|CHAPA_SECRET_KEY=${CHAPA_SECRET_KEY:-}|g" .env
sed -i "s|CHAPA_CALLBACK_URL=.*|CHAPA_CALLBACK_URL=${CHAPA_CALLBACK_URL:-}|g" .env
sed -i "s|CHAPA_RETURN_URL=.*|CHAPA_RETURN_URL=${CHAPA_RETURN_URL:-}|g" .env
sed -i "s|CHAPA_WEBHOOK_URL=.*|CHAPA_WEBHOOK_URL=${CHAPA_WEBHOOK_URL:-}|g" .env
sed -i "s|CLOUDINARY_CLOUD_NAME=.*|CLOUDINARY_CLOUD_NAME=${CLOUDINARY_CLOUD_NAME:-}|g" .env
sed -i "s|CLOUDINARY_KEY=.*|CLOUDINARY_KEY=${CLOUDINARY_KEY:-}|g" .env
sed -i "s|CLOUDINARY_SECRET=.*|CLOUDINARY_SECRET=${CLOUDINARY_SECRET:-}|g" .env

php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache

php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
