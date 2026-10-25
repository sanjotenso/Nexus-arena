FROM php:8.2-apache

RUN a2enmod rewrite \
    && docker-php-ext-install pdo_sqlite \
    && printf '%s\n' '<Directory /var/www/html>' '    AllowOverride All' '    Require all granted' '</Directory>' \
        > /etc/apache2/conf-available/nexus.conf \
    && a2enconf nexus

WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data /var/www/html/assets

EXPOSE 80