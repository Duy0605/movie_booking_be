#!/usr/bin/env bash
set -e
echo "Running composer"
composer install --no-dev --optimize-autoloader --working-dir=/var/www/html
echo "Caching config..."
php artisan config:cache
echo "Caching routes..."
php artisan route:cache
echo "Running migrations..."
php artisan migrate --force
echo "Stopping PHP-FPM (if running)..."
service php-fpm stop || true
echo "Starting PHP-FPM..."
service php-fpm start
echo "Checking PHP-FPM status..."
service php-fpm status || (echo "PHP-FPM failed to start" && exit 1)
echo "Checking port 9000..."
netstat -tuln | grep 9000 || echo "Port 9000 not listening"