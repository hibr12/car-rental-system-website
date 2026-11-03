# syntax=docker/dockerfile:1

FROM node:22-bookworm-slim AS frontend-build

WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./
RUN --mount=type=secret,id=proxy_ca,required=false \
    if [ -f /run/secrets/proxy_ca ]; then \
        export NODE_EXTRA_CA_CERTS=/run/secrets/proxy_ca; \
    fi; \
    npm ci --strict-ssl=true --no-audit --no-fund

COPY frontend/ ./
RUN npm run build


FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        mbstring \
        pcntl \
        pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    --mount=type=secret,id=proxy_ca,required=false \
    composer_preference=dist; \
    if [ -f /run/secrets/proxy_ca ]; then \
        export COMPOSER_CAFILE=/run/secrets/proxy_ca; \
        export GIT_SSL_CAINFO=/run/secrets/proxy_ca; \
        composer_preference=source; \
    fi; \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader \
        --prefer-install="$composer_preference"; \
    find vendor -type d -name .git -prune -exec rm -rf {} +

COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# The SPA and API share one origin. This keeps Laravel Sanctum's session and
# CSRF cookies first-party and makes React Router deep links work on Render.
COPY --from=frontend-build /app/frontend/dist/ ./public/

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
