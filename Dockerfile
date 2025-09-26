FROM php:8.2-fpm

WORKDIR /var/www

# Instal dependensi sistem dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip

# Instal Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 1. Salin hanya file composer.json dan composer.lock terlebih dahulu.
# Trik ini memanfaatkan cache Docker. Jika file-file ini tidak berubah,
# Docker tidak akan menjalankan "composer install" lagi, sehingga build lebih cepat.
COPY src/composer.json src/composer.lock ./

# 2. Jalankan composer install.
# Ini akan menginstal SEMUA paket yang ada di composer.json, termasuk driver S3.
# --no-dev: Jangan instal paket untuk development (lebih ramping untuk produksi).
# --optimize-autoloader: Optimasi autoloader untuk performa.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY src/ .

# Atur izin untuk folder yang butuh akses tulis oleh web server
RUN chown -R www-data:www-data storage bootstrap/cache