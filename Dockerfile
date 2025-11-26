FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY src/composer.json src/composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
