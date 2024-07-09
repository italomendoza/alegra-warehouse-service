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
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd sockets



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

# Crear archivos de registro y ajustar permisos
RUN mkdir -p /var/log/supervisor \
    && touch /var/log/supervisor/supervisord.log \
    && touch /var/log/supervisor/apache2.log \
    && touch /var/log/supervisor/laravel-worker.log \
    && touch /var/log/supervisor/worker.log \
    && touch /var/log/supervisor/rabbitmq-consumer.log \
    && chmod 777 /var/log/supervisor/supervisord.log \
    && chmod 777 /var/log/supervisor/apache2.log \
    && chmod 777 /var/log/supervisor/laravel-worker.log \
    && chmod 777 /var/log/supervisor/worker.log \
    && chmod 777 /var/log/supervisor/rabbitmq-consumer.log

RUN mkdir -p /var/run \
    && touch /var/run/supervisord.pid \
    && chmod 777 /var/run/supervisord.pid

# Copiar configuración de Supervisor
COPY ./supervisord/supervisord.conf /etc/supervisord.conf

# Cambiar permisos para los archivos ejecutables
RUN chmod 755 /etc/supervisord.conf

USER www-data
WORKDIR /var/www/html/
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data .env.example .env


ARG APP_ENV=build
ENV APP_ENV=${APP_ENV}

RUN composer install --ignore-platform-reqs

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
# CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf", "-n"]
