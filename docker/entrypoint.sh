#!/bin/bash
set -e

cd /var/www/html

# Installe les dépendances Composer si besoin (volume vendor vide au 1er démarrage)
if [ ! -f vendor/autoload.php ]; then
  echo "[docker] composer install…"
  composer install --no-interaction --prefer-dist --ignore-platform-req=ext-mongodb
fi

mkdir -p logs/mails
chown -R www-data:www-data logs 2>/dev/null || true

exec apache2-foreground
