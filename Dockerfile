FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
        libpq-dev libzip-dev libonig-dev libxml2-dev libsqlite3-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql pdo_sqlite zip mbstring dom \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs storage/app/backups \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
