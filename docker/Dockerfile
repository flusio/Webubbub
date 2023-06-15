FROM php:7.4-fpm

ENV COMPOSER_HOME /tmp

RUN apt-get update \
    && apt-get install -y git libzip-dev unzip \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer/composer /usr/bin/composer /usr/bin/composer
