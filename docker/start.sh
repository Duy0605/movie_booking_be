# Optimized start script - minimal logging

# Create .env file
cat > /var/www/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=${APP_ENV:-production}  
APP_KEY=${APP_KEY:-}
APP_DEBUG=${true}
APP_URL=${APP_URL:-http://localhost}
APP_CIPHER=${APP_CIPHER:-AES-256-CBC}

LOG_CHANNEL=single
LOG_LEVEL=error

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
EOF

# Generate APP_KEY if not provided
if [ -z "$APP_KEY" ]; then
    KEY_BASE64=$(openssl rand -base64 32)
    sed -i "s/APP_KEY=.*/APP_KEY=base64:$KEY_BASE64/" /var/www/.env
fi

# Set ownership
chown -R www:www /var/www

# Clear caches (silent)
php artisan config:clear --no-interaction >/dev/null 2>&1
php artisan cache:clear --no-interaction >/dev/null 2>&1  
php artisan route:clear --no-interaction >/dev/null 2>&1
php artisan view:clear --no-interaction >/dev/null 2>&1

# Create storage link (silent)  
php artisan storage:link --no-interaction >/dev/null 2>&1

mkdir -p /var/www/storage/logs
touch /var/www/storage/logs/laravel.log
chown -R www:www /var/www/storage
chmod -R 775 /var/www/storage

echo "===== .env file ====="
cat /var/www/.env

echo "===== Laravel log ====="
tail -n 50 /var/www/storage/logs/laravel.log || true

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini

tail -f /var/www/storage/logs/laravel.log &