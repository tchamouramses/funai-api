FROM php:8.4-apache

WORKDIR /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -i "s|/var/www/html|${APACHE_DOCUMENT_ROOT}|g" /etc/apache2/sites-available/000-default.conf && \
    apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    zip unzip git curl netcat-traditional libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    libonig-dev libxml2-dev libzip-dev libssl-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd mbstring pdo pdo_mysql zip opcache bcmath intl && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

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
