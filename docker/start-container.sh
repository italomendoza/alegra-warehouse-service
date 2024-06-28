#!/bin/sh

# Corre migraciones y seeders
php artisan migrate --force
php artisan db:seed --force

# Inicia PHP-FPM
php-fpm
