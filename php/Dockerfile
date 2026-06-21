FROM composer:2.9.3 AS composer

FROM php:8.5-fpm

ARG NEW_RELIC_AGENT_VERSION=12.4.0.29

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
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
    && curl -fsSL "https://download.newrelic.com/php_agent/archive/${NEW_RELIC_AGENT_VERSION}/newrelic-php5-${NEW_RELIC_AGENT_VERSION}-linux.tar.gz" -o /tmp/newrelic-php-agent.tar.gz \
    && mkdir -p /tmp/newrelic-php-agent \
    && tar -xzf /tmp/newrelic-php-agent.tar.gz -C /tmp/newrelic-php-agent --strip-components=1 \
    && NR_INSTALL_SILENT=1 NR_INSTALL_USE_CP_NOT_LN=1 /tmp/newrelic-php-agent/newrelic-install install \
    && rm -f /usr/local/etc/php/conf.d/newrelic.ini \
    && rm -rf /tmp/newrelic-php-agent /tmp/newrelic-php-agent.tar.gz \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY conf.d/ /usr/local/etc/php/conf.d/
COPY php-fpm.d/ /usr/local/etc/php-fpm.d/

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
