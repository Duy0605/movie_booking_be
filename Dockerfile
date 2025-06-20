# Dockerfile - Optimized for speed
FROM php:8.2-fpm-alpine

# Install system dependencies in one layer
RUN apk add --no-cache \
    git curl libpng-dev oniguruma-dev libxml2-dev zip unzip \
    nginx supervisor mysql-client freetype-dev libjpeg-turbo-dev \
    libpng freetype libjpeg-turbo

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user and directories
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www \
    && mkdir -p /var/log/supervisor /var/log/nginx /var/www/storage/{logs,framework/{sessions,views,cache},app/public} /var/www/bootstrap/cache

# Set working directory
WORKDIR /var/www

# Copy and install dependencies
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# Copy application
COPY . .

# Set permissions in one step
RUN chown -R www:www /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor.d/supervisord.ini
COPY docker/start.sh /start.sh

RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]