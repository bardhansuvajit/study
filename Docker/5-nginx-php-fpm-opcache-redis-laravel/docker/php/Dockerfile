FROM php:8.4-fpm

# Arguments for user ID mapping
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache \
    mysqli \
    bcmath \
    sockets \
    pcntl \
    gd \
    mbstring \
    exif \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user with same UID/GID as host user
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data

WORKDIR /var/www/html

# Set proper permissions for Laravel storage and cache directories
RUN mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Set proper permissions for the entire application directory
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
