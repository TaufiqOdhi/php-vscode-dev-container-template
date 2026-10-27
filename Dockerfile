FROM php:8.4-apache-bookworm

# Composer is copied from its official image so it is available in terminals too.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl mbstring mysqli opcache pdo_mysql zip \
    && pecl install xdebug-3.5.3 \
    && docker-php-ext-enable xdebug \
    && a2enmod rewrite \
    && groupadd --gid 1000 vscode \
    && useradd --uid 1000 --gid vscode --create-home --shell /bin/bash vscode \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/development.ini /usr/local/etc/php/conf.d/99-development.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug-settings.ini

WORKDIR /var/www/html
