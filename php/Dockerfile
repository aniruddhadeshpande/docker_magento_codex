FROM composer:2.9.3 AS composer

FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsodium-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        ftp \
        gd \
        intl \
        mbstring \
        pdo_mysql \
        soap \
        sockets \
        xsl \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
