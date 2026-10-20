#!/bin/sh
set -e

# Setup SQLite database if configured
DB_CONN="${DB_CONNECTION:-sqlite}"
FRESH_DB=false
if [ "$DB_CONN" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/app/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    if [ ! -f "$DB_FILE" ]; then
        echo "Creating SQLite database file at $DB_FILE..."
        touch "$DB_FILE"
        FRESH_DB=true
    fi
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
    chmod 775 "$(dirname "$DB_FILE")"
    chmod 664 "$DB_FILE"
fi

# Ensure storage and cache directories exist with correct permissions
mkdir -p /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/bootstrap/cache

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is missing. Generating new key..."
    php artisan key:generate --force --no-interaction
fi

# Run database migrations if enabled
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
    if [ "$FRESH_DB" = "true" ]; then
        echo "Fresh database detected, seeding default admin user..."
        php artisan db:seed --force --no-interaction || true
    fi
fi

# Optimize Laravel caching in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing Laravel configuration and routes caching..."
    php artisan optimize || true
fi

echo "Starting FrankenPHP server..."
exec "$@"
