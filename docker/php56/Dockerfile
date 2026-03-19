FROM php:5.6-apache

ENV DEBIAN_FRONTEND=noninteractive

RUN printf '%s\n' \
        'deb [trusted=yes] http://archive.debian.org/debian stretch main' \
        'deb [trusted=yes] http://archive.debian.org/debian-security stretch/updates main' \
        > /etc/apt/sources.list \
    && printf '%s\n' \
        'Acquire::Check-Valid-Until "false";' \
        'Acquire::AllowInsecureRepositories "true";' \
        'Acquire::AllowDowngradeToInsecureRepositories "true";' \
        > /etc/apt/apt.conf.d/99archive \
    && apt-get update \
    && apt-get install -y --no-install-recommends --allow-unauthenticated \
        git \
        unzip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libxpm-dev \
        libicu-dev \
        libmcrypt-dev \
        libxml2-dev \
        zlib1g-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        libxslt1-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-webp-dir=/usr/include/ \
        --with-xpm-dir=/usr/include/ \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        mbstring \
        mcrypt \
        mysqli \
        mysql \
        pdo \
        pdo_mysql \
        soap \
        xml \
        zip

RUN a2enmod rewrite headers expires

COPY docker/php56/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php56/php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini
COPY docker/php56/entrypoint.sh /usr/local/bin/phpbooru-entrypoint

RUN chmod +x /usr/local/bin/phpbooru-entrypoint

WORKDIR /var/www/project

ENTRYPOINT ["phpbooru-entrypoint"]
CMD ["apache2-foreground"]

