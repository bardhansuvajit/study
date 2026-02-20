FROM php:8.4-fpm

# Install MySQL extension for PHP (opcache - optional)
# RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install pdo pdo_mysql opcache 

WORKDIR /var/www/html
COPY ./src /var/www/html