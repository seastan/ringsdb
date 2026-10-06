FROM php:8.1-alpine

RUN apk update && apk add --no-cache \
    libzip-dev \
    libpng-dev \
    linux-headers

RUN docker-php-ext-install pdo_mysql gd zip

# Xdebug (dev stack), used for code coverage (make coverage).
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-3.4.5 \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache bash
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli

WORKDIR /var/www/html
