FROM php:8.2-apache

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

RUN a2enmod rewrite

# Меняем корневую директорию на public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80