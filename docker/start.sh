#!/bin/sh
# docker/start.sh

# Create necessary directories
mkdir -p /var/log/supervisor /var/log/nginx /run/php

# Ensure PHP-FPM can create socket
chown www:www /run/php

# Debug: Check if PHP-FPM exists
echo "Checking PHP-FPM..."
which php-fpm
php-fpm --version
php-fpm -t

# Debug: Check nginx config
echo "Testing nginx config..."
nginx -t

# Create .env file from environment variables
cat > /var/www/.env << EOF
APP_NAME=${APP_NAME:-Laravel}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost}
APP_CIPHER=${APP_CIPHER:-AES-256-CBC}

LOG_CHANNEL=stack
LOG_LEVEL=debug

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

# Ensure proper ownership first
echo "Setting ownership..."
chown -R www:www /var/www

# Recreate storage directories with proper permissions
echo "Setting up storage permissions..."
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Clear old cache files that might have wrong permissions
rm -rf /var/www/storage/framework/views/*
rm -rf /var/www/storage/framework/cache/*
rm -rf /var/www/storage/framework/sessions/*
rm -rf /var/www/bootstrap/cache/*

# Set proper permissions with more specific approach
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache
chown -R www:www /var/www/storage
chown -R www:www /var/www/bootstrap/cache

# Ensure specific directories have write permissions
chmod 777 /var/www/storage/framework/views
chmod 777 /var/www/storage/framework/cache
chmod 777 /var/www/storage/framework/sessions
chmod 777 /var/www/storage/logs

# FIX: Generate proper Laravel APP_KEY
echo "Generating Laravel application key..."

# Check if APP_KEY is already set and valid
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "APP_KEY not provided, generating new one..."
    
    # Method 1: Try using Laravel's key:generate command
    if php artisan key:generate --force --no-interaction --show 2>/dev/null; then
        echo "Successfully generated key using artisan"
    else
        echo "Artisan key generation failed, using manual method..."
        
        # Method 2: Generate 32-byte key manually and encode properly
        # Generate 32 random bytes and base64 encode them
        KEY_BYTES=$(openssl rand 32)
        KEY_BASE64=$(echo -n "$KEY_BYTES" | base64 -w 0)
        
        # Update the .env file with properly formatted key
        sed -i "s/APP_KEY=.*/APP_KEY=base64:$KEY_BASE64/" /var/www/.env
        
        echo "Generated manual APP_KEY: base64:$KEY_BASE64"
    fi
else
    echo "Using provided APP_KEY: $APP_KEY"
    # Ensure the provided key has the correct format
    if [[ ! "$APP_KEY" =~ ^base64: ]]; then
        echo "Warning: APP_KEY doesn't start with 'base64:', fixing format..."
        sed -i "s/APP_KEY=.*/APP_KEY=base64:$APP_KEY/" /var/www/.env
    fi
fi

# Verify the APP_KEY is set correctly
echo "Final APP_KEY configuration:"
grep "APP_KEY=" /var/www/.env

# Test Laravel configuration before proceeding
echo "Testing Laravel configuration..."
php artisan config:clear --no-interaction
php artisan cache:clear --no-interaction

# Test if the key works by trying to access config
if php artisan tinker --execute="echo 'Key test: ' . config('app.key');" 2>/dev/null; then
    echo "APP_KEY validation successful"
else
    echo "APP_KEY validation failed, trying alternative generation..."
    
    # Last resort: Generate a proper 256-bit key
    # For AES-256-CBC, we need exactly 32 bytes (256 bits)
    RANDOM_KEY=$(dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64 -w 0)
    sed -i "s/APP_KEY=.*/APP_KEY=base64:$RANDOM_KEY/" /var/www/.env
    
    echo "Used fallback key generation: base64:$RANDOM_KEY"
fi

# Run composer scripts now that .env exists
echo "Running composer scripts..."
composer run-script post-autoload-dump --no-interaction

# Clear and cache config
echo "Clearing caches..."
php artisan config:clear --no-interaction || echo "Config clear failed - continuing"
php artisan cache:clear --no-interaction || echo "Cache clear failed - continuing"
php artisan route:clear --no-interaction || echo "Route clear failed - continuing"  
php artisan view:clear --no-interaction || echo "View clear failed - continuing"

# Try to run a simple artisan command to test
echo "Testing artisan..."
php artisan --version

# Test database connection with better error handling
if [ ! -z "$DB_HOST" ]; then
    echo "Testing database connection..."
    php artisan migrate:status 2>&1 || echo "Database connection failed - this is expected if DB is not ready"
fi

# Create storage link
echo "Creating storage link..."
php artisan storage:link --no-interaction || echo "Storage link creation failed - continuing"

# Final permission setup - ensure www user owns everything
echo "Final permission setup..."
chown -R www:www /var/www
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Make sure nginx can read files
chmod 755 /var/www
chmod 755 /var/www/public

# Debug: Show Laravel logs before starting
echo "Checking Laravel logs..."
touch /var/www/storage/logs/laravel.log
chown www:www /var/www/storage/logs/laravel.log
tail -n 20 /var/www/storage/logs/laravel.log 2>/dev/null || echo "No logs yet"

echo "Starting supervisor..."
# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor.d/supervisord.ini