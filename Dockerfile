FROM php:8.4-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install gd mbstring pdo pdo_mysql zip opcache bcmath intl pcntl sockets

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --no-dev --prefer-dist

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
CMD sh -c "echo 'Waiting for database...'; \
    echo 'Database is up!'; \
    php artisan migrate --force; \
    php artisan storage:link; \
    php artisan optimize \
    php artisan queue:work -q > /var/log/queue.log 2>&1 & \
    echo 'Starting Laravel Schedule worker...'; \
    php artisan schedule:work -q > /var/log/schedulelog.log 2>&1 & \
    tail -f /var/log/queue.log & \
    exec apache2-foreground"
