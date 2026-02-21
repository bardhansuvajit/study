FROM php:8.4-fpm

# Install MySQL extension for PHP (opcache - optional)
RUN docker-php-ext-install pdo pdo_mysql opcache mysqli \
    && docker-php-ext-install bcmath sockets pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY ./src /var/www/html