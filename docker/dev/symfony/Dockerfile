FROM php:7.1-alpine

RUN apk update && apk add --no-cache \
    libzip-dev \
    libpng-dev

RUN docker-php-ext-install pdo_mysql gd zip

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html