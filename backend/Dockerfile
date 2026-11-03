# syntax=docker/dockerfile:1

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

# The license form accepts two files up to 5 MiB each. PHP's stock 2 MiB
# upload limit rejected valid files before Laravel could validate them, and
# development error display could turn that failure into an HTTP 200 response.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'upload_max_filesize=6M'; \
        echo 'post_max_size=16M'; \
        echo 'max_file_uploads=10'; \
        echo 'display_errors=Off'; \
        echo 'display_startup_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'max_execution_time=120'; \
        echo 'max_input_time=120'; \
    } > "$PHP_INI_DIR/conf.d/zz-app-upload.ini"

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
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

COPY . ./
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

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
