# Use the official PHP 7.4 FPM image as the base
FROM php:7.4-fpm-bullseye

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    curl \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_pgsql zip intl mbstring

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

#RUN composer install --no-dev --optimize-autoloader

EXPOSE 9000

CMD ["php-fpm"]
