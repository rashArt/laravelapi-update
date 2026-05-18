#!/bin/sh
# Corregir permisos de directorios de escritura de Laravel antes de iniciar php-fpm
# Necesario porque el bind mount desde Windows puede montar con owner root:root
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

exec php-fpm
