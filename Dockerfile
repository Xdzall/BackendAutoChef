# Stage 1: ambil Composer binary dari image resmi
FROM composer:2.7 AS composer

# Stage 2: PHP image untuk aplikasi
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

# Salin composer dari stage pertama
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Salin file composer.json dan composer.lock dulu (agar cache efisien)
COPY src/composer.json src/composer.lock ./

# Jalankan composer install
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Salin semua kode Laravel
COPY src/ .

# Atur permission untuk folder yang butuh akses tulis
RUN chown -R www-data:www-data storage bootstrap/cache






# # Dockerfile lama tanpa multi-stage build

# FROM php:8.2-fpm

# WORKDIR /var/www

# # Instal dependensi sistem dan ekstensi PHP
# RUN apt-get update && apt-get install -y \
#     git \
#     unzip \
#     libpq-dev \
#     libzip-dev \
#     && pecl install redis \
#     && docker-php-ext-enable redis \
#     && docker-php-ext-install pdo pdo_pgsql zip

# # Instal Composer
# RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# # 1. Salin hanya file composer.json dan composer.lock terlebih dahulu.
# # Trik ini memanfaatkan cache Docker. Jika file-file ini tidak berubah,
# # Docker tidak akan menjalankan "composer install" lagi, sehingga build lebih cepat.
# COPY src/composer.json src/composer.lock ./

# # 2. Jalankan composer install.
# # Ini akan menginstal SEMUA paket yang ada di composer.json, termasuk driver S3.
# # --no-dev: Jangan instal paket untuk development (lebih ramping untuk produksi).
# # --optimize-autoloader: Optimasi autoloader untuk performa.
# RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# COPY src/ .

# # Atur izin untuk folder yang butuh akses tulis oleh web server
# RUN chown -R www-data:www-data storage bootstrap/cache