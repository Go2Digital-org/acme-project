#!/bin/sh
set -e

echo "🚀 Starting ACME CSR Platform with FrankenPHP..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until php artisan db:monitor 2>/dev/null | grep -q "OK"; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "✅ MySQL is ready!"

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Clear caches for development
echo "🔥 Clearing caches for development..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "✨ Application ready!"

# Start FrankenPHP (worker mode configured in Caddyfile)
echo "🚀 Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile