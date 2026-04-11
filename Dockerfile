# Production Dockerfile for Symfony using FrankenPHP
FROM dunglas/frankenphp:1-php8.3-alpine AS frankenphp_upstream

FROM frankenphp_upstream AS frankenphp_base

# Install system dependencies
RUN apk add --no-cache \
    acl \
    file \
    gettext \
    git \
    ;

# Install PHP extensions
RUN set -eux; \
    install-php-extensions \
        apcu \
        intl \
        opcache \
        zip \
        pdo_pgsql \
    ;

# Set working directory
WORKDIR /app

# Copy configuration files
COPY ./.docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY ./.docker/frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/

# Production environment
ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import /etc/caddy/Caddyfile"

# Use the production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress;

# Copy the rest of the application
COPY . .

# Finish composer installation
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --optimize --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod -R 777 var;

# Set up healthcheck
HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD curl -f http://localhost:2019/metrics || exit 1
