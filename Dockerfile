FROM webdevops/php-nginx:8.1

# Update system packages to mitigate vulnerabilities
RUN apt-get update && apt-get upgrade -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy application code
COPY . /var/www/html
WORKDIR /var/www/html

# Copy Nginx config
COPY conf/nginx/nginx.conf /opt/docker/etc/nginx/vhost.conf

# Set permissions
RUN chown -R application:application /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Environment variables
ENV WEB_DOCUMENT_ROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
# Disable unnecessary PHP modules to reduce attack surface
ENV PHP_DISMOD=bz2,calendar,exif,ffi,intl,gettext,ldap,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsem,sysvshm,shmop,xsl,zip,gd,apcu,vips,yaml,imagick,mongodb,amqp

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 80
EXPOSE 80

# Copy and run deploy script
COPY deploy.sh /deploy.sh
RUN chmod +x /deploy.sh

# Start Nginx and PHP
CMD ["/deploy.sh"]