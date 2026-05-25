FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash git unzip icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
