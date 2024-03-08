FROM php:8.2-apache
RUN apt-get update && apt-get install -y curl wget git openssl libonig-dev libzip-dev libpq-dev
RUN docker-php-ext-install -j$(nproc) mbstring mysqli zip
RUN a2enmod rewrite
WORKDIR /var/www/

COPY start.sh /tmp/start.sh
COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

ENTRYPOINT ["bash", "/tmp/start.sh"]