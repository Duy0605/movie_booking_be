#!/bin/bash
# Enhanced start script with debugging

echo "Starting application..."

# Create necessary directories
mkdir -p /var/log/nginx /var/www/storage/logs

# Create .env file
cat > /var/www/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=${APP_ENV:-production}  
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost}
APP_CIPHER=${APP_CIPHER:-AES-256-CBC}

LOG_CHANNEL=single
LOG_LEVEL=debug
LOG_STDERR_FORMATTER=Monolog\\Formatter\\JsonFormatter

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
DB_SSLMODE=${DB_SSLMODE:-require}
MYSQL_ATTR_SSL_CA=${MYSQL_ATTR_SSL_CA:-}
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=${MYSQL_ATTR_SSL_VERIFY_SERVER_CERT:-false}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=hiepnguyenduy2003@gmail.com
MAIL_PASSWORD=uvkqrhdcrulmmuli
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hiepnguyenduy2003@gmail.com
MAIL_FROM_NAME="Web dat ve xem film"

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

PUSHER_APP_ID=${PUSHER_APP_ID:-}
PUSHER_APP_KEY=${PUSHER_APP_KEY:-}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-}
PUSHER_HOST=${PUSHER_HOST:-}
PUSHER_PORT=${PUSHER_PORT:-443}
PUSHER_SCHEME=${PUSHER_SCHEME:-https}
PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-mt1}

SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost}
FRONTEND_URL=${FRONTEND_URL:-https://movie-ticket-murex.vercel.app}

# Trust proxies for Render
TRUSTED_PROXIES=*
TRUSTED_HOSTS=^(.+\.)?render\.com$,^(.+\.)?onrender\.com$
EOF

# Generate APP_KEY if not provided
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    KEY_BASE64=$(openssl rand -base64 32)
    sed -i "s/APP_KEY=.*/APP_KEY=base64:$KEY_BASE64/" /var/www/.env
fi

# Set ownership
chown -R www:www /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Running Laravel setup commands..."

# Clear caches with error handling
php artisan config:clear --no-interaction || echo "Config clear failed"
php artisan cache:clear --no-interaction || echo "Cache clear failed"
php artisan route:clear --no-interaction || echo "Route clear failed"  
php artisan view:clear --no-interaction || echo "View clear failed"

# Test database connection
echo "Testing database connection..."
php artisan migrate:status || echo "Database connection failed"

# Create storage link
php artisan storage:link --no-interaction || echo "Storage link failed"

# Cache config for production
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache --no-interaction || echo "Config cache failed"
fi

echo "Starting services..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini