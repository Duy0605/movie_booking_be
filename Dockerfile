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

# Don't remove the libraries needed at runtime
# RUN apk del --purge libpng-dev oniguruma-dev libxml2-dev freetype-dev libjpeg-turbo-dev

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

# Set working directory for composer
WORKDIR /var/www

# Install dependencies without running scripts to avoid env issues
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www:www . /var/www

# Create necessary directories
RUN mkdir -p /var/www/storage/logs
RUN mkdir -p /var/www/storage/framework/sessions
RUN mkdir -p /var/www/storage/framework/views
RUN mkdir -p /var/www/storage/framework/cache

# Set permissions
RUN chown -R www:www /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy supervisor config
COPY docker/supervisord.conf /etc/supervisor.d/supervisord.ini

# Copy startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]