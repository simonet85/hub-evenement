FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring gd zip intl bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY .docker/php/php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD ["sh", "-c", "cd /var/www/html/public && exec php -S 0.0.0.0:8000 /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
