FROM php:7.4-apache

ENV APP_IS_LOCALHOST=1

# Set apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/site
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install PHP extensions
RUN apt-get update -y && apt-get install -y openssl zip unzip git libjpeg-dev libpng-dev libxml2-dev libzip-dev
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install mysqli pdo pdo_mysql gd fileinfo iconv xml zip exif

# Install Composer dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Enable Apache modules
RUN a2enmod rewrite

EXPOSE 80

CMD [ "bash", "/var/www/dev/startup.sh" ]