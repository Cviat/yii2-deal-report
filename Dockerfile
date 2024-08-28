FROM php:8.2-fpm

# Установите зависимости для Yii2 и расширения PHP
RUN apt-get update && \
    apt-get install -y libxml2-dev git unzip && \
    docker-php-ext-install mysqli pdo pdo_mysql

# Установите Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создайте рабочую директорию и скопируйте код приложения
WORKDIR /var/www/html
COPY . .

# Установите зависимости проекта
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --verbose || \
    (echo "Composer install failed" && exit 1)

# Откройте порты
EXPOSE 80

# Запустите сервер
CMD ["php", "-S", "0.0.0.0:80", "-t", "web/"]
