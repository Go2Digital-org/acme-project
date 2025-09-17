# ================================================================
# ACME Corp CSR Platform - Optimized FrankenPHP + Octane Docker Image
# Multi-stage build with superior performance and caching
# Supports multi-platform builds (AMD64/ARM64)
# ================================================================

ARG FRANKENPHP_VERSION=1-php8.4-alpine
ARG COMPOSER_VERSION=2.8
ARG NODE_VERSION=22-alpine

# ================================================================
# COMPOSER STAGE - Isolated dependency resolution
# ================================================================
FROM composer:${COMPOSER_VERSION} AS composer

WORKDIR /app

# Copy composer files for dependency resolution
COPY composer.json composer.lock ./

# Install production dependencies (cached layer)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    && composer clear-cache

# ================================================================
# NODE STAGE - Isolated asset building
# ================================================================
FROM node:${NODE_VERSION} AS node

WORKDIR /app

# Copy package files for dependency resolution
COPY package.json package-lock.json ./

# Install npm dependencies (cached layer)
RUN npm ci --only=production --no-audit --no-fund

# Copy source files for building
COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

# Build production assets
RUN npm run build

# ================================================================
# BASE STAGE - Common dependencies and configuration
# ================================================================
FROM dunglas/frankenphp:${FRANKENPHP_VERSION} AS frankenphp_base

# Set working directory
WORKDIR /app

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=16 \
    PHP_REALPATH_CACHE_SIZE=4096K \
    PHP_REALPATH_CACHE_TTL=600

# Install system dependencies (single layer for efficiency)
RUN apk add --no-cache --virtual .build-deps \
        autoconf \
        g++ \
        make \
        libtool \
        pkgconfig \
    && apk add --no-cache \
        # System utilities
        acl \
        fcgi \
        file \
        gettext \
        git \
        gnu-libiconv \
        # Database clients
        mysql-client \
        postgresql-client \
        # Cache and queue
        redis \
        # Process management
        supervisor \
        # Security and monitoring
        curl \
        htop \
        # Image processing
        imagemagick \
        # Archive tools
        zip \
        unzip \
        # Network tools
        openssh-client \
        rsync

# Install PHP extensions (optimized order for caching)
RUN install-php-extensions \
        # Database
        pdo_mysql \
        pdo_pgsql \
        # Math and crypto
        bcmath \
        # Image processing
        gd \
        imagick/imagick@master \
        exif \
        # Internationalization
        intl \
        # Archive
        zip \
        # Cache and session
        redis \
        opcache \
        # Process control
        pcntl \
        sockets \
        # Additional
        soap \
        xml \
        mbstring \
        iconv \
        fileinfo \
        tokenizer \
        ctype \
        json \
        filter \
        hash

# Cleanup build dependencies
RUN apk del .build-deps \
    && rm -rf /var/cache/apk/* \
    && rm -rf /tmp/*

# Configure PHP for optimal performance
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy optimized PHP configuration
COPY docker/php/php.ini $PHP_INI_DIR/conf.d/99-acme.ini

# Set up directory structure and permissions
RUN mkdir -p \
        /app/storage/app/public \
        /app/storage/framework/cache \
        /app/storage/framework/sessions \
        /app/storage/framework/views \
        /app/storage/logs \
        /app/bootstrap/cache \
        /var/log/supervisor \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# ================================================================
# DEVELOPMENT STAGE - Development environment with debugging
# ================================================================
FROM frankenphp_base AS frankenphp_dev

# Install development dependencies
RUN apk add --no-cache \
        nodejs \
        npm \
        # Development tools
        bash \
        vim \
        strace \
        # Browser testing
        chromium \
        chromium-chromedriver \
        xvfb

# Install development PHP extensions
RUN install-php-extensions xdebug

# Configure Xdebug for development and coverage
RUN echo "xdebug.mode=develop,debug,coverage" >> $PHP_INI_DIR/conf.d/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> $PHP_INI_DIR/conf.d/xdebug.ini \
    && echo "xdebug.client_port=9003" >> $PHP_INI_DIR/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> $PHP_INI_DIR/conf.d/xdebug.ini \
    && echo "xdebug.discover_client_host=true" >> $PHP_INI_DIR/conf.d/xdebug.ini \
    && echo "xdebug.max_nesting_level=256" >> $PHP_INI_DIR/conf.d/xdebug.ini

# Configure PHP for development
RUN echo "memory_limit=2G" > $PHP_INI_DIR/conf.d/dev.ini \
    && echo "max_execution_time=300" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "error_reporting=E_ALL" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "display_errors=On" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "display_startup_errors=On" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "log_errors=On" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "opcache.validate_timestamps=1" >> $PHP_INI_DIR/conf.d/dev.ini \
    && echo "opcache.revalidate_freq=0" >> $PHP_INI_DIR/conf.d/dev.ini

# Configure FrankenPHP for development with Octane
COPY docker/frankenphp/Caddyfile.dev /etc/caddy/Caddyfile

# Chrome/Chromium configuration for browser testing
ENV CHROME_BIN=/usr/bin/chromium-browser \
    CHROME_PATH=/usr/lib/chromium/ \
    DISPLAY=:99 \
    OCTANE_SERVER=frankenphp \
    OCTANE_HOST=0.0.0.0 \
    OCTANE_PORT=80 \
    OCTANE_WORKERS=auto \
    OCTANE_MAX_REQUESTS=500

EXPOSE 80 443 443/udp 9003

# Copy entrypoint scripts
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/entrypoint-supervisor.sh /usr/local/bin/entrypoint-supervisor.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/entrypoint-supervisor.sh

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/

# Copy cron configuration
COPY docker/cron/laravel-cron /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron

# Development entrypoint with supervisor for complete process management
# Use ENABLE_SUPERVISOR=true to enable supervisor mode
ENTRYPOINT ["/usr/local/bin/entrypoint-supervisor.sh"]

# ================================================================
# TESTING STAGE - CI/CD testing environment
# ================================================================
FROM frankenphp_dev AS frankenphp_test

# Install additional testing dependencies
RUN apk add --no-cache \
        # Playwright dependencies
        chromium \
        firefox \
        webkit2gtk \
        # Additional testing tools
        sqlite \
        # Coverage tools
        lcov

# Install code coverage extension
RUN install-php-extensions pcov

# Configure PHP for testing
RUN echo "memory_limit=2G" > $PHP_INI_DIR/conf.d/testing.ini \
    && echo "pcov.enabled=1" >> $PHP_INI_DIR/conf.d/testing.ini \
    && echo "pcov.directory=/app" >> $PHP_INI_DIR/conf.d/testing.ini \
    && echo "max_execution_time=0" >> $PHP_INI_DIR/conf.d/testing.ini

# Copy application (for testing)
COPY . .

# Install all dependencies including dev
RUN composer install --prefer-dist --no-progress --no-interaction

# Install npm dependencies and build assets
RUN npm ci \
    && npm run build

# Set proper permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Configure environment for testing
ENV APP_ENV=testing \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    CACHE_DRIVER=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync \
    OCTANE_SERVER=frankenphp

# Default command for testing
CMD ["./vendor/bin/pest", "--coverage"]

# ================================================================
# PRODUCTION STAGE - Optimized production environment
# ================================================================
FROM frankenphp_base AS frankenphp_prod

# Production-specific environment variables
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    OCTANE_SERVER=frankenphp \
    OCTANE_HOST=0.0.0.0 \
    OCTANE_PORT=80 \
    OCTANE_WORKERS=auto \
    OCTANE_MAX_REQUESTS=1000 \
    OCTANE_HTTPS=false

# Configure PHP for production
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/production.ini $PHP_INI_DIR/conf.d/production.ini

# Copy composer dependencies from composer stage
COPY --from=composer --chown=www-data:www-data /app/vendor /app/vendor

# Copy built assets from node stage
COPY --from=node --chown=www-data:www-data /app/public/build /app/public/build

# Copy application source
COPY --chown=www-data:www-data . .

# Generate autoloader with production optimizations
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Remove development files and optimize
RUN rm -rf \
        .git \
        .github \
        .editorconfig \
        .env.example \
        .gitignore \
        .styleci.yml \
        docker-compose*.yml \
        Dockerfile* \
        phpunit.xml \
        tests \
        node_modules \
        package*.json \
        vite.config.js \
        tailwind.config.js \
        postcss.config.js \
        resources/js \
        resources/css \
        resources/sass

# Set proper ownership and permissions
RUN chown -R www-data:www-data /app \
    && find /app -type f -exec chmod 644 {} \; \
    && find /app -type d -exec chmod 755 {} \; \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chmod 755 /app/artisan

# Configure FrankenPHP for production with Octane
COPY docker/frankenphp/Caddyfile.prod /etc/caddy/Caddyfile

# Copy supervisor configuration for unified process management
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Health check for production
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

EXPOSE 80 443 443/udp

# Use supervisor to manage all processes in production
USER www-data
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ================================================================
# MULTI-PLATFORM BUILD METADATA
# ================================================================
LABEL maintainer="ACME Corp DevOps Team" \
      version="2.0" \
      description="ACME Corp CSR Platform - FrankenPHP + Laravel Octane" \
      org.opencontainers.image.source="https://github.com/acme-corp/acme-corp-optimy" \
      org.opencontainers.image.title="ACME CSR Platform" \
      org.opencontainers.image.description="Enterprise CSR platform with FrankenPHP and Laravel Octane" \
      org.opencontainers.image.vendor="ACME Corporation" \
      org.opencontainers.image.licenses="Proprietary"