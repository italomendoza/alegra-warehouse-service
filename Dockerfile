FROM --platform=linux/amd64 php:8.2-apache
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd



COPY php "$PHP_INI_DIR/conf.d/"
COPY apache /etc/apache2/
COPY --from=composer:2.3.7 /usr/bin/composer /usr/bin/composer
# ADD https://github.com/elastic/apm-agent-php/archive/refs/tags/v1.6.1.tar.gz /srv/apm.tar.gz
# RUN mkdir -p /usr/src/php/ext \
#     && cd /srv/ && tar zxf /srv/apm.tar.gz \
#     && mv /srv/apm-agent-php-1.6.1/src/ext /usr/src/php/ext/apm-agent \
#     && docker-php-ext-install apm-agent \
#     && docker-php-ext-enable elastic_apm
RUN a2enmod rewrite
USER www-data
WORKDIR /var/www/html/
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data .env.example .env
RUN composer install --ignore-platform-reqs
RUN php artisan key:generate
EXPOSE 80

