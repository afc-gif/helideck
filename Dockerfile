FROM node:20-bookworm-slim AS node
FROM composer:2 AS composer

FROM php:8.3-cli-bookworm

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        git \
        libonig-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY backend/composer.json backend/composer.lock ./backend/
RUN cd backend \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

COPY . .

RUN cd backend \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi

CMD ["bash", "start-railway.sh"]
