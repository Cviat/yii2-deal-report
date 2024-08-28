FROM php:8.2-fpm


RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install mysqli pdo pdo_mysql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html
COPY . .


RUN composer install --no-interaction --prefer-dist --optimize-autoloader


EXPOSE 80


CMD ["php", "-S", "0.0.0.0:80", "-t", "web/"]
