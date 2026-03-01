FROM php:8.4-cli

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Устанавливаем PHP расширения
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd zip

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаём пользователя для безопасности
RUN useradd -G www-data,root -u 1000 -d /home/app app
RUN mkdir -p /home/app/.composer && \
    chown -R app:app /home/app

# Копируем файлы проекта
WORKDIR /var/www
COPY . .

# Устанавливаем зависимости Composer
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Создаём .env и генерируем ключ
RUN cp .env.example .env && \
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan storage:link

# Создаём директорию для SQLite
RUN mkdir -p /data && chown -R app:app /data

# Переключаемся на пользователя app
USER app

# Открываем порт
EXPOSE $PORT

# Запускаем миграции и сервер
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
