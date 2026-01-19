# syntax=docker/dockerfile:1

# =============================================================================
# GitPulse Application Dockerfile
# =============================================================================
# Multi-stage build for production-ready Laravel application
#
# Stages:
#   1. base       - PHP extensions and system dependencies
#   2. composer   - PHP dependencies
#   3. frontend   - Node.js build for frontend assets
#   4. production - Final optimized image
#
# Build:
#   docker build -t gitpulse:latest .
#
# Run:
#   docker run -p 8000:8000 gitpulse:latest
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Base PHP Image
# -----------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    zip \
    unzip \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    gd \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    zip

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# -----------------------------------------------------------------------------
# Stage 2: Composer Dependencies
# -----------------------------------------------------------------------------
FROM base AS composer

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies (no dev, optimized autoloader)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# -----------------------------------------------------------------------------
# Stage 3: Frontend Build
# -----------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --ignore-scripts

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.ts tsconfig.json tailwind.config.ts ./
COPY postcss.config.js ./

# Copy PHP files needed for Wayfinder generation
COPY --from=composer /app/vendor ./vendor
COPY app ./app
COPY routes ./routes
COPY bootstrap ./bootstrap
COPY config ./config
COPY artisan ./

# Build frontend assets
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 4: Production Image
# -----------------------------------------------------------------------------
FROM base AS production

# Set working directory
WORKDIR /var/www/html

# Create non-root user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Copy application code
COPY --chown=www:www . .

# Copy vendor from composer stage
COPY --from=composer --chown=www:www /app/vendor ./vendor

# Copy built frontend assets
COPY --from=frontend --chown=www:www /app/public/build ./public/build

# Copy nginx and supervisor configs
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www:www storage bootstrap/cache

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

# Optimize Laravel for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8000/up || exit 1

# Start supervisor (manages nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

# -----------------------------------------------------------------------------
# Development Image (optional, for local development)
# -----------------------------------------------------------------------------
FROM base AS development

# Install development tools
RUN apk add --no-cache \
    nodejs \
    npm

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Xdebug for debugging
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Use development PHP config
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Create non-root user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
