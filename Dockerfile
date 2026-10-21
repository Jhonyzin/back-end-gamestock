FROM php:8.3-apache

# postgres
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/gamestock.conf \
    && a2enconf gamestock \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# porta do render
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

RUN printf 'upload_max_filesize=6M\npost_max_size=8M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/gamestock.ini

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000
