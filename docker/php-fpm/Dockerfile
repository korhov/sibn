FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    zip \
    wget \
    zlib1g-dev \
    libmcrypt-dev \
    libzip-dev \
    libpq-dev

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    ln -s $(composer config --global home) /root/composer
ENV PATH=$PATH:/root/composer/vendor/bin COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /symfony

ADD composer.json /symfony
ADD composer.lock /symfony

# Для кеширования vendor-ов
RUN composer install --no-dev --no-scripts --no-autoloader

# source
ADD source.tar.gz /symfony

RUN ls -la

RUN composer install
# --optimize-autoloader --no-dev