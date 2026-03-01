FROM richarvey/nginx-php-fpm:3.1.0

COPY . .

# Image config
ENV SKIP_COMPOSER 0
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG true
ENV LOG_CHANNEL stderr
ENV SESSION_DRIVER database
ENV CACHE_STORE database
ENV QUEUE_CONNECTION database

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Create .env if not exists and generate app key
RUN if [ ! -f .env ]; then cp .env.example .env; fi && \
    composer install --optimize-autoloader --no-dev --no-interaction --no-scripts || true && \
    php artisan key:generate --force || true && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan storage:link || true

CMD ["/start.sh"]
