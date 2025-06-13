#!/bin/bash
set -e

echo "Running composer"
composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting PHP built-in server..."
php -S 127.0.0.1:8000 -t /var/www/html/public &

echo "Starting Nginx..."
nginx -g 'daemon off;'