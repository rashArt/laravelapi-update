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
    # Instalar extensión phpredis 6.3.0 (última versión estable) para conexión con Redis
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

# Copiar entrypoint que corrige permisos de storage antes de iniciar php-fpm
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]