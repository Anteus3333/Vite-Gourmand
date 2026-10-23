# PHP 8.4 + Apache — aligné Infomaniak (version recommandée)
FROM php:8.4-apache

# Extensions PHP (PDO MySQL, GD, MongoDB pour stats admin)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
# mongodb 1.21.x : compatible avec mongodb/mongodb 1.21 du composer.lock
# (l’extension 2.x casse composer install avec ce lock)
RUN install-php-extensions pdo_mysql gd mongodb-1.21.12 zip

# Composer (install des packages au démarrage via entrypoint)
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

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/app-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["app-entrypoint.sh"]
