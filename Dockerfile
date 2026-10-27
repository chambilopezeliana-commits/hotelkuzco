FROM composer:2 AS composer

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && cp -a public /opt/public \
    && chown -R www-data:www-data storage bootstrap/cache public

USER www-data

CMD ["sh", "-c", "cp -a /opt/public/. /var/www/html/public/ && php artisan migrate --force && php artisan db:seed --class=UserSeeder --force && php artisan config:cache && php-fpm"]
