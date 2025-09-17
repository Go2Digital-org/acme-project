#!/bin/sh
set -e

echo "🚀 Starting ACME CSR Platform with Supervisor..."

# Simple wait for MySQL to be ready (give it time to start)
echo "⏳ Waiting for services to initialize..."
sleep 10

# Check if MySQL is accessible (but don't block if it fails)
echo "🔍 Checking MySQL connection..."
php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=acme_corp_csr', 'acme', 'secret');
    echo '✅ MySQL is ready!' . PHP_EOL;
} catch (Exception \$e) {
    echo '⚠️  MySQL connection warning: ' . \$e->getMessage() . PHP_EOL;
    echo '   Continuing anyway...' . PHP_EOL;
}
"

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Clear caches for development
echo "🔥 Clearing caches for development..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Setup Meilisearch indexes
echo "🔍 Setting up Meilisearch indexes..."
if [ -f /app/scripts/meilisearch-setup.sh ]; then
    bash /app/scripts/meilisearch-setup.sh
else
    echo "⚠️  Meilisearch setup script not found, skipping index setup"
fi

# Note: Laravel scheduler is handled by supervisor (see supervisord.conf)
# No need for separate cron daemon as supervisor handles scheduling

# Create supervisor log directory if it doesn't exist
mkdir -p /var/log/supervisor

# Copy environment-specific supervisor config
echo "📋 Configuring supervisor for environment: ${APP_ENV:-production}"
if [ "$APP_ENV" = "staging" ]; then
    if [ -f /app/.github/deployment/staging/supervisor-workers.conf ]; then
        cp /app/.github/deployment/staging/supervisor-workers.conf /etc/supervisor/conf.d/workers.conf
        echo "✅ Staging supervisor configuration loaded"
    fi
else
    if [ -f /app/.github/deployment/production/supervisor-workers.conf ]; then
        cp /app/.github/deployment/production/supervisor-workers.conf /etc/supervisor/conf.d/workers.conf
        echo "✅ Production supervisor configuration loaded"
    fi
fi

echo "✨ Application ready!"

# Start supervisor to manage all processes
echo "🎯 Starting Supervisor to manage all services..."
echo "   - FrankenPHP (Web Server)"
echo "   - Horizon (Queue Management)"
echo "   - Scheduler (Cron Tasks)"
echo "   - Queue Workers (All Priorities)"
echo "   - Scout Indexer (Meilisearch)"

# Use the main supervisord.conf which includes all services
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf