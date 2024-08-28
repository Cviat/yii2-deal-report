
FROM php:8.2-cli

WORKDIR /var/www/html


RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install mysqli pdo pdo_mysql



COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


COPY . /var/www/html


RUN composer install --no-interaction --prefer-dist --optimize-autoloader


EXPOSE 80


CMD ["php", "-S", "0.0.0.0:80", "-t", "web/"]