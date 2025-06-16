# Dockerfile
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    mysql-client \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng \
    freetype \
    libjpeg-turbo

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Create supervisor log directory and nginx log directory
RUN mkdir -p /var/log/supervisor /var/log/nginx

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock /var/www/

# Install dependencies without running scripts to avoid env issues
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy existing application directory contents
COPY . /var/www

# Create necessary directories with proper permissions BEFORE changing ownership
RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/framework/cache \
    /var/www/storage/app/public \
    /var/www/bootstrap/cache

# Set ownership and permissions properly
RUN chown -R www:www /var/www \
    && find /var/www/storage -type f -exec chmod 664 {} \; \
    && find /var/www/storage -type d -exec chmod 775 {} \; \
    && find /var/www/bootstrap/cache -type f -exec chmod 664 {} \; \
    && find /var/www/bootstrap/cache -type d -exec chmod 775 {} \; \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy PHP-FPM config
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor config
COPY docker/supervisord.conf /etc/supervisor.d/supervisord.ini

# Copy startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]