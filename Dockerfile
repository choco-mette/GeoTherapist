# ==========================================
# Stage 1: Build Frontend Assets (Vite)
# ==========================================
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --prefer-offline

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ==========================================
# Stage 2: Install Composer Dependencies
# ==========================================
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs

# ==========================================
# Stage 3: Ultra-lightweight FrankenPHP Runtime
# ==========================================
FROM dunglas/frankenphp:1-php8.3-alpine AS runner

# Install essential PHP extensions for Laravel, SQLite, MySQL, and PostgreSQL
RUN install-php-extensions \
    pdo_sqlite \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    zip \
    opcache \
    pcntl \
    intl \
    && rm -rf /tmp/* /var/cache/apk/*

# Production PHP settings with optimized Opcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/memory_limit = 128M/memory_limit = 256M/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/post_max_size = 8M/post_max_size = 25M/' "$PHP_INI_DIR/php.ini" && \
    { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
    } >> "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

WORKDIR /app

# Copy Composer vendor from stage 2
COPY --from=vendor /app/vendor /app/vendor

# Copy application source code
COPY . /app

# Copy built frontend assets from stage 1
COPY --from=frontend /app/public/build /app/public/build

# Remove dev/temp files and any local cached providers
RUN rm -rf node_modules tests .git .github bootstrap/cache/*.php

# Discover installed Laravel packages
RUN php artisan package:discover --ansi

# Setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Environment defaults
ENV SERVER_NAME=":80"
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV AUTO_MIGRATE=true

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
