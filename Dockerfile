FROM php:8.2-fpm

# Установка необходимых зависимостей
RUN apt-get update && \
    apt-get install -y libxml2-dev && \
    docker-php-ext-install mysqli pdo pdo_mysql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание рабочей директории и копирование файлов проекта
WORKDIR /var/www/html
COPY . .

# Установка зависимостей проекта
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --verbose

# Открытие порта 80
EXPOSE 80

# Запуск встроенного PHP-сервера
CMD ["php", "-S", "0.0.0.0:80", "-t", "web/"]
