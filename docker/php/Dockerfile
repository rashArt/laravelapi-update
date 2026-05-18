FROM rash07/php:8.3

# Instalar dependencias del sistema y extensiones PHP necesarias:
# - zip: compresión de archivos
# - sqlite3 / libsqlite3-dev: soporte SQLite para tests en memoria
RUN apt-get update && apt-get install -y \
        libzip-dev \
        libsqlite3-dev \
        sqlite3 \
        zip \
        unzip \
        curl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

CMD ["php-fpm"]