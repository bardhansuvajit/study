FROM php:8.4-fpm

# Install MySQL extension for PHP
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY ./src /var/www/html