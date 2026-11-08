#!/bin/sh
set -e

# Set up Laravel directories that need to be writable
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/cache
mkdir -p /app/storage/framework/views
mkdir -p /app/storage/logs
mkdir -p /app/storage/app/private
mkdir -p /app/bootstrap/cache

# Set ownership and permissions for Laravel storage and cache
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Export PORT for nginx and other processes
PORT="${PORT:-8000}"
export PORT

# Template nginx config with the PORT variable
mkdir -p /etc/nginx/http.d
if command -v envsubst >/dev/null 2>&1; then
  envsubst '$PORT' < /etc/nginx/http.d/app.conf.template > /etc/nginx/http.d/app.conf
fi

# Ensure log directory exists for supervisor
mkdir -p /var/log/supervisor

cd /app
php artisan migrate --force --no-interaction
php artisan webstudio:ensure-admin --no-interaction

exec "$@"