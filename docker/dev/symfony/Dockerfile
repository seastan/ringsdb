FROM php:7.1-alpine

RUN apk update && apk add --no-cache \
    libzip-dev \
    libpng-dev

RUN docker-php-ext-install pdo_mysql gd zip

# Xdebug (dev stack), used for code coverage (make coverage). Xdebug 2.9.8 is the last version
# supporting PHP 7.1 (Xdebug 3 requires PHP 7.2+).
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
