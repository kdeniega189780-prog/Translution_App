#!/bin/sh
set -eu
test -n "${APP_KEY:-}" || { echo 'APP_KEY is required'; exit 1; }
sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/\*:10000/*:${PORT:-10000}/" /etc/apache2/sites-available/000-default.conf
php artisan config:cache
php artisan migrate --force
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
