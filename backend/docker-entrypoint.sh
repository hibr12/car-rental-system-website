#!/bin/bash
set -e

echo "=== Laravel Production Entrypoint ==="

# Wait for database to be ready (optional, but helpful)
echo "Checking database connection..."
until php artisan db:show --no-interaction 2>/dev/null; do
    echo "Waiting for database..."
    sleep 2
done
echo "Database connection OK"

# Run migrations (safe, no data loss)
echo "Running migrations..."
php artisan migrate --force --no-interaction

# Clear and cache config (requires all env vars to be set)
echo "Caching configuration..."
php artisan config:clear --no-interaction
php artisan config:cache --no-interaction

# Cache routes
echo "Caching routes..."
php artisan route:clear --no-interaction
php artisan route:cache --no-interaction

# Cache views
echo "Caching views..."
php artisan view:clear --no-interaction
php artisan view:cache --no-interaction

# Storage permissions
echo "Setting storage permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "=== Starting Laravel Server ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}