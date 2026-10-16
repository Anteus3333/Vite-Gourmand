# PHP 8.2 + Apache — environnement Docker ECF (Vite et Gourmand)
FROM php:8.2-apache

# Extensions PHP (PDO MySQL, GD, MongoDB pour stats admin)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd mongodb zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# DocumentRoot = public/ + réécriture d'URL (.htaccess)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite \
    && printf '<Directory %s>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        "${APACHE_DOCUMENT_ROOT}" \
        > /etc/apache2/conf-available/document-root-directory.conf \
    && a2enconf document-root-directory

WORKDIR /var/www/html

# Dépendances PHP (vendor) — le code source est monté en volume en local
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    || composer install --no-interaction --no-scripts

COPY . .

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/app-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["app-entrypoint.sh"]
