#!/bin/bash
set -e

cd /var/www/html

mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

echo "[Dispatch] DB_HOST=${DB_HOST:-not-set}"

# Keep the web server up even if DB is briefly unavailable.
php artisan migrate --force || echo "[Dispatch] migrate failed — check DB host/network in Coolify"
php artisan db:seed --force || echo "[Dispatch] seed failed"
php artisan storage:link || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "[Dispatch] Starting Apache on port 80"
exec apache2-foreground
