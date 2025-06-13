FROM richarvey/nginx-php-fpm:3.1.6
COPY . /var/www/html
COPY conf/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/conf.d/default.conf
COPY conf/php-fpm/www.conf /etc/php/8.1/fpm/pool.d/www.conf
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN chmod +x /var/www/html/deploy.sh
CMD ["/start.sh"]