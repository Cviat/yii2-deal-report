FROM php:8.2-fpm


RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install mysqli pdo pdo_mysql

# Установите Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html
COPY . .


RUN composer install

RUN composer install --no-interaction

EXPOSE 80


CMD ["php-fpm"]
