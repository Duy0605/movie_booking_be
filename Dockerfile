# Dockerfile - Fixed for Render
FROM php:8.2-fpm-alpine

# Install system dependencies in one layer
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev zip unzip \
    nginx supervisor mysql-client freetype-dev libjpeg-turbo-dev \
    libpng freetype libjpeg-turbo bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user and directories
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www \
    && mkdir -p /var/log/supervisor /var/log/nginx \
    && mkdir -p /var/www/storage/{logs,framework/{sessions,views,cache},app/public} \
    && mkdir -p /var/www/bootstrap/cache

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# Copy docker configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor.d/supervisord.ini
COPY docker/ca.pem /var/www/docker/ca.pem

# Copy start script and make it executable
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Copy application code
COPY . .

# Set correct permissions
RUN chown -R www:www /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create .htaccess for security (if needed)
RUN echo "Options -Indexes" > /var/www/public/.htaccess

EXPOSE 80

# Use the full path to start script
CMD ["/usr/local/bin/start.sh"]