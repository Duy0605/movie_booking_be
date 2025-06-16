#!/bin/sh
# docker/start.sh

# Generate application key if not exists
if [ ! -f /var/www/.env ]; then
    cp /var/www/.env.example /var/www/.env
fi

# Generate key if APP_KEY is empty
if grep -q "APP_KEY=$" /var/www/.env; then
    php artisan key:generate --force
fi

# Clear and cache config
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations (optional, be careful in production)
# php artisan migrate --force

# Cache config for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini