#!/bin/bash
set -e

# ACME Corp CSR Platform - Unified Container Startup Script
# Initializes all application processes under Supervisor management

echo "=== ACME Corp CSR Platform - Startup ==="
echo "Environment: ${APP_ENV:-production}"
echo "Starting unified container with FrankenPHP + Supervisor..."

# Set default environment if not specified
export APP_ENV=${APP_ENV:-production}

# Load environment-specific worker configuration
ENV_FILE="/etc/supervisor/env/${APP_ENV}.env"
if [ -f "$ENV_FILE" ]; then
    echo "Loading worker configuration from $ENV_FILE"
    set -a  # automatically export all variables
    source "$ENV_FILE"
    set +a
else
    echo "Warning: No environment file found for $APP_ENV, using defaults"
    # Default worker counts (minimal)
    export QUEUE_PAYMENTS_WORKERS=1
    export QUEUE_NOTIFICATIONS_WORKERS=1
    export QUEUE_EXPORTS_WORKERS=1
    export QUEUE_REPORTS_WORKERS=1
    export QUEUE_DEFAULT_WORKERS=1
    export QUEUE_BULK_WORKERS=1
    export QUEUE_MAINTENANCE_WORKERS=1
    export QUEUE_CACHE_WARMING_WORKERS=1
fi

# Wait for database connection
echo "Waiting for database connection..."
until php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done
echo "Database connection established"

# Wait for Redis connection
echo "Checking Redis connection..."
until php artisan tinker --execute="Redis::ping();" >/dev/null 2>&1; do
    echo "Redis not ready, waiting..."
    sleep 2
done
echo "Redis connection established"

# Run Laravel optimizations
echo "Running Laravel optimizations..."
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

# Create storage symlink if needed
if [ ! -L "/app/public/storage" ]; then
    echo "Creating storage symlink..."
    php artisan storage:link --quiet
fi

# Migrate database if in production/staging
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

echo "=== Worker Configuration ==="
echo "Payments: $QUEUE_PAYMENTS_WORKERS workers"
echo "Notifications: $QUEUE_NOTIFICATIONS_WORKERS workers"
echo "Exports: $QUEUE_EXPORTS_WORKERS workers"
echo "Reports: $QUEUE_REPORTS_WORKERS workers"
echo "Default: $QUEUE_DEFAULT_WORKERS workers"
echo "Bulk: $QUEUE_BULK_WORKERS workers"
echo "Maintenance: $QUEUE_MAINTENANCE_WORKERS workers"
echo "Cache Warming: $QUEUE_CACHE_WARMING_WORKERS workers"

TOTAL_WORKERS=$((
    QUEUE_PAYMENTS_WORKERS + 
    QUEUE_NOTIFICATIONS_WORKERS + 
    QUEUE_EXPORTS_WORKERS + 
    QUEUE_REPORTS_WORKERS + 
    QUEUE_DEFAULT_WORKERS + 
    QUEUE_BULK_WORKERS + 
    QUEUE_MAINTENANCE_WORKERS + 
    QUEUE_CACHE_WARMING_WORKERS
))
echo "Total Queue Workers: $TOTAL_WORKERS"

# Set permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache

echo "=== Starting Supervisor ==="
echo "All processes will be managed by Supervisor"
echo "Monitor with: supervisorctl status"

# Start Supervisor in foreground mode
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf