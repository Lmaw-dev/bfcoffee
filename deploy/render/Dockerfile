FROM php:8.1-apache

# Install MySQL extension and common tools
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git && \
    docker-php-ext-install pdo pdo_mysql && \
    a2enmod rewrite

# Copy application files
WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80

CMD ["apache2-foreground"]
