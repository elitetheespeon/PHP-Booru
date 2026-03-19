FROM php:8.4-apache

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libonig-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libxpm-dev \
        libxml2-dev \
        zlib1g-dev \
        libcurl4-openssl-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
        --with-xpm \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        soap \
        xml \
        zip

# NOTE: mysql and mcrypt extensions were used in PHP 5.6 but are not available in PHP 8.4.

RUN a2enmod rewrite headers expires

COPY docker/php84/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php84/php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini
COPY docker/php84/entrypoint.sh /usr/local/bin/phpbooru-entrypoint

RUN chmod +x /usr/local/bin/phpbooru-entrypoint

WORKDIR /var/www/project

ENTRYPOINT ["phpbooru-entrypoint"]
CMD ["apache2-foreground"]
