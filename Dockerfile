FROM php:8.3-cli

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
    libsqlite3-dev \
    default-libmysqlclient-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем файлы проекта
WORKDIR /var/www
COPY . .

# Обновляем composer и устанавливаем зависимости
RUN composer --version && \
    composer clear-cache || true && \
    composer install --optimize-autoloader --no-dev --no-interaction --ignore-platform-reqs || composer install --optimize-autoloader --no-dev --no-interaction

# Создаём .env и генерируем ключ
RUN if [ ! -f .env ]; then cp .env.example .env; fi && \
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan storage:link

# Создаём директорию для SQLite (резерв)
RUN mkdir -p /data && chown -R www-data:www-data /data

# Открываем порт
EXPOSE $PORT

# Запускаем миграции и сервер
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
