# =========================================================
# GameStock - back-end PHP para o Render (Web Service com Docker)
# O Render não roda PHP nativamente, por isso usamos esta imagem
# oficial com Apache + PHP 8.3.
# =========================================================
FROM php:8.3-apache

# Extensões do PDO: PostgreSQL (banco do Render) e MySQL (XAMPP)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# mod_rewrite (rotas pelo .htaccess) e mod_headers
RUN a2enmod rewrite headers

# Permite o .htaccess funcionar (AllowOverride All) e evita aviso de ServerName
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/gamestock.conf \
    && a2enconf gamestock \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# O Render informa a porta na variável PORT (padrão 10000).
# O Apache lê ${PORT} na hora de iniciar.
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Limites de upload (foto de perfil 2 MB, imagem de jogo 5 MB)
RUN printf 'upload_max_filesize=6M\npost_max_size=8M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/gamestock.ini

# Código da API
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 10000
