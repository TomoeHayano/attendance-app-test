FROM composer:2 AS composer_stage

FROM php:8.2-fpm

COPY php.ini /usr/local/etc/php/

RUN apt update \
  && apt install -y default-mysql-client zlib1g-dev libzip-dev unzip \
  && docker-php-ext-install pdo_mysql zip

COPY --from=composer_stage /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
