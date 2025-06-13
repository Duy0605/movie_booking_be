FROM richarvey/nginx-php-fpm:3.1.6

# Install dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application code
COPY . /var/www/html
WORKDIR /var/www/html

# Copy Nginx config
COPY conf/nginx/nginx.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Environment variables
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 80
EXPOSE 80

# Copy and run deploy script
COPY deploy.sh /deploy.sh
RUN chmod +x /deploy.sh

# Start Nginx and PHP server
CMD ["/deploy.sh"]