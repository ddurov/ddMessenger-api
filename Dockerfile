FROM php:8.2-fpm

RUN apt-get update && apt-get install -y curl wget git zip openssl libonig-dev libpq-dev libzip-dev
RUN docker-php-ext-install -j$(nproc) mbstring mysqli zip

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.json
RUN COMPOSER_ALLOW_SUPERUSER=1 composer update
COPY service/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY service/start.sh start.sh
COPY service/cli.php cli.php

ENTRYPOINT ["bash", "start.sh"]