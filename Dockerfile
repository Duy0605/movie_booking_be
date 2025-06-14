FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    redis-tools \
    nginx \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader || true

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN rm -rf /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* /usr/share/nginx/html/*

# Configure PHP-FPM
RUN echo "[www]\nuser = www-data\ngroup = www-data\nlisten = /var/run/php/php8.4-fpm.sock\nlisten.owner = www-data\nlisten.group = www-data\nlisten.mode = 0660\npm = dynamic\npm.start_servers = 2\npm.min_spare_servers = 1\npm.max_spare_servers = 3\npm.max_children = 5\n" > /usr/local/etc/php-fpm.d/www.conf

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache \
    && mkdir -p /var/run/php \
    && chown www-data:www-data /var/run/php \
    && chmod 755 /var/run/php

# Expose port 80 for Nginx
EXPOSE 80

# Start Nginx and PHP-FPM with debugging
CMD ["/bin/bash", "-c", "php-fpm -t && nginx -t && nginx -g 'daemon off;' && php-fpm"]