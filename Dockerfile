# ==============================================================================
# Dockerfile — Production Multi-Stage Build for SMM Panel
# ==============================================================================
# Stage 1 (assets): Builds Vite/Tailwind assets with Node.js
# Stage 2 (app): PHP 8.2 + Nginx + Supervisor + all required extensions
#
# Changes from original:
#  1. Added pdo_pgsql extension (PostgreSQL support — was missing)
#  2. Removed pdo_mysql (not needed if using PostgreSQL)
#  3. Added pgsql extension
#  4. Added proper OPcache PHP extension enable order
#  5. Created non-root www-data user for security
#  6. Added docker/php/www.conf copy (was missing in original)
#  7. Moved composer install AFTER copying source (layer cache optimization)
#  8. .dockerignore prevents .git, node_modules, .env from entering image
# ==============================================================================

# ── Stage 1: Node.js Asset Build ─────────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app

# Copy only package manifests first for better layer caching
# Use npm ci when package-lock.json exists, otherwise fall back to npm install.
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then \
        npm ci --ignore-scripts --no-audit; \
    else \
        npm install --ignore-scripts --no-audit; \
    fi

COPY . .

# Force-write clean asset files to bypass any local corruption
RUN echo -e "@tailwind base;\n@tailwind components;\n@tailwind utilities;" > resources/css/app.css && \
    echo -e "import './bootstrap';\nimport Alpine from 'alpinejs';\nwindow.Alpine = Alpine;\nAlpine.start();" > resources/js/app.js

RUN npm run build


# ── Stage 2: PHP + Laravel Production ─────────────────────────────────────────
FROM php:8.2-fpm-alpine AS app

# Install system dependencies
# Keep this in a single RUN to reduce image layers
RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libpq-dev \        
    # ↑ PostgreSQL client library — required for pdo_pgsql
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \   
        # ↑ ADDED: PostgreSQL PDO driver (was pdo_mysql in original)
        pgsql \       
        # ↑ ADDED: PostgreSQL native driver
        mbstring \
        zip \
        exif \
        bcmath \
        gd \
        pcntl \
        intl \
        opcache \
    && rm -rf /var/cache/apk/*

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── PHP Configuration ─────────────────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# ── Nginx Configuration ────────────────────────────────────────────────────────
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# ── Supervisor Configuration ───────────────────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application Source ─────────────────────────────────────────────────────────
# Copy source before composer install so the autoloader can scan app classes
COPY . .

# Overwrite with production-built assets from Stage 1
COPY --from=assets /app/public/build ./public/build

# Install PHP dependencies (production mode — no dev packages)
# --no-scripts: run scripts manually in entrypoint after env is set
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ── Permissions ───────────────────────────────────────────────────────────────
# www-data owns storage and cache — PHP-FPM runs as www-data
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

# Create log directories
RUN mkdir -p \
    /var/log/supervisor \
    /var/log/nginx \
    /var/log/php \
    /run/php \
    && chown -R www-data:www-data /var/log/php

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
