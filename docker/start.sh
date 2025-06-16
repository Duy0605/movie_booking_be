#!/bin/sh
# docker/start.sh

# Create .env file from environment variables
cat > /var/www/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=hiepnguyenduy2003@gmail.com
MAIL_PASSWORD=uvkqrhdcrulmmuli
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hiepnguyenduy2003@gmail.com
MAIL_FROM_NAME="Web dat ve xem film"

BROADCAST_DRIVER=${BROADCAST_CONNECTION:-log}
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
FRONTEND_URL=${FRONTEND_URL:-http://localhost:3000}
EOF

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run composer scripts now that .env exists
composer run-script post-autoload-dump

# Clear and cache config
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini