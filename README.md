# Backend API — Laravel 11

API RESTful de gestión de inventario y productos. Migrada desde Laravel 8 a **Laravel 11** con PHP 8.2+, arquitectura moderna y buenas prácticas actualizadas.

## Stack tecnológico

| Componente | Versión / Detalle |
|---|---|
| Framework | Laravel 11.x |
| PHP | 8.3 |
| Base de datos | MySQL 8.0 |
| Caché / Colas | Redis (alpine) |
| Servidor web | Nginx (proxy inverso → php-fpm) |
| Contenedores | Docker + Docker Compose |

---

## Infraestructura Docker

El proyecto utiliza cuatro servicios orquestados mediante Docker Compose:

### Servicios

#### `nginx` — Servidor web
- **Imagen**: `rash07/nginx:base`
- **Contenedor**: `legacy_nginx`
- **Puerto expuesto**: `8081:80`
- Actúa como proxy inverso, enruta las peticiones PHP al servicio `php-fpm` en el puerto 9000.
- Configuración montada desde `./nginx/default.conf`.

#### `php` — PHP-FPM 8.3
- **Imagen base**: `rash07/php:8.3` (imagen personalizada construida desde `./docker/php/Dockerfile`)
- **Contenedor**: `legacy_php`
- Extensiones instaladas: `pdo_mysql`, `pdo_sqlite`, `zip`, `redis` (phpredis 6.3.0)
- SQLite disponible para la ejecución de pruebas en memoria.
- El `entrypoint.sh` corrige permisos de `storage/` y `bootstrap/cache/` antes de iniciar php-fpm (necesario en bind mounts desde Windows).
- Composer 2 incluido en la imagen.

#### `mysql` — Base de datos
- **Imagen**: `mysql:8.0`
- **Contenedor**: `legacy_mysql`
- **Puerto expuesto**: `3307:3306` (evita conflicto con MySQL local)
- Datos persistidos en el volumen `legacy_mysql_data`.
- Credenciales configuradas mediante variables de entorno del `.env`.

#### `redis` — Caché y colas
- **Imagen**: `redis:alpine`
- **Contenedor**: `legacy_redis`
- Datos persistidos en el volumen `legacy_redis_data`.
- Utilizado como driver de caché y colas de Laravel.

### Red
Todos los servicios se comunican a través de la red externa `legacy-network`.

> Si la red no existe, créala antes de levantar los contenedores:
> ```bash
> docker network create legacy-network
> ```

---

## Despliegue con Docker

### 1. Clonar el repositorio y preparar el entorno

```bash
git clone <repo-url>
cd laravelapi-update
cp .env.example .env
```

Editar `.env` y configurar al menos:

```env
DB_HOST=legacy_mysql
DB_PORT=3306
DB_DATABASE=legacy_db
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=legacy_redis
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Crear la red Docker (solo la primera vez)

```bash
docker network create legacy-network
```

### 3. Construir y levantar los contenedores

```bash
docker compose up -d --build
```

Para ver el estado de los contenedores:

```bash
docker compose ps
```

### 4. Instalar dependencias PHP

```bash
docker exec legacy_php composer install --no-interaction --prefer-dist --optimize-autoloader
```

### 5. Generar la clave de aplicación

```bash
docker exec legacy_php php artisan key:generate
```

### 6. Ejecutar migraciones

```bash
docker exec legacy_php php artisan migrate
```

### 7. Ejecutar seeders

```bash
docker exec legacy_php php artisan db:seed
```

O en un solo paso (migraciones + seeders):

```bash
docker exec legacy_php php artisan migrate --seed
```

### 8. Optimizar para producción (opcional)

```bash
docker exec legacy_php php artisan config:cache
docker exec legacy_php php artisan route:cache
docker exec legacy_php php artisan view:cache
```

---

## Ejecutar pruebas

Las pruebas utilizan SQLite en memoria, no requieren conexión a MySQL.

```bash
# Todos los tests
docker exec legacy_php php artisan test tests/Feature --colors=never

# Categorías
docker exec legacy_php php artisan test tests/Feature --filter="CategoryCrudTest" --colors=never

# Productos
docker exec legacy_php php artisan test tests/Feature --filter="ProductCrudTest" --colors=never

# Movimientos de stock
docker exec legacy_php php artisan test tests/Feature --filter="StockMovementCrudTest" --colors=never

# Smoke test del sistema
docker exec legacy_php php artisan test tests/Feature --filter="LegacySmokeTest" --colors=never
```

---

## URL base

```
http://localhost:8081/api
```

## Credenciales de prueba

```
email:    admin@legacy.test
password: password
```

El token API del usuario semilla puede usarse directamente en los clientes REST:

```
token: ouQWurpHdU68MIOsaZl3F8eQItVwAIWNrv1eP0ElmQ1Fy2miyQRlgLs9VQr1
```

---

## Endpoints disponibles

```
POST   /api/login

GET    /api/categories
POST   /api/categories
PUT    /api/categories/{id}
DELETE /api/categories/{id}

GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}

GET    /api/products/{id}/stock-movements
POST   /api/products/{id}/stock-movements

GET    /api/dashboard
GET    /api/health
```

Todos los endpoints (excepto `/login` y `/health`) requieren el header:

```
Authorization: Bearer {token}
```

---

## Pruebas con REST Client

El directorio `rest.client/` contiene archivos `.http` listos para usarse con la extensión **REST Client** de VS Code (`humao.rest-client`).

### Archivos disponibles

| Archivo | Descripción |
|---|---|
| `rest.client/login.http` | Health check y autenticación |
| `rest.client/categories.http` | CRUD de categorías y filtro por nombre |
| `rest.client/products.http` | Listado, filtrado y movimientos de stock |
| `rest.client/dashboard.http` | Resumen del inventario |

### Ejemplo de respuesta — Login

```json
POST /api/login
→ 200 OK

{
  "status": true,
  "code": 200,
  "data": {
    "token": "ouQWurpHdU68MIOsaZl3F8eQItVwAIWNrv1eP0ElmQ1Fy2miyQRlgLs9VQr1"
  }
}
```

### Ejemplo de respuesta — Listado de productos

```json
GET /api/products
→ 200 OK

{
  "status": true,
  "code": 200,
  "data": [
    {
      "id": 9460,
      "name": "Producto Legacy 9460",
      "description": "Producto generado para prueba de rendimiento 9460",
      "price": "62.14",
      "stock": 79,
      "status": true,
      "category_id": 95,
      "category": {
        "id": 95,
        "name": "Categoria 95"
      },
      "created_at": "2026-05-18T18:28:50.000000Z",
      "updated_at": "2026-05-18T18:28:50.000000Z"
    }
  ],
  "links": { "next": "...cursor..." },
  "meta": { "per_page": 15, "next_cursor": "..." }
}
```

### Ejemplo de respuesta — Dashboard

```json
GET /api/dashboard
→ 200 OK

{
  "status": true,
  "code": 200,
  "data": {
    "products": 10000,
    "categories": 100,
    "low_stock": [ { "id": 8, "name": "Producto Legacy 8", "stock": 3, ... } ],
    "last_movements": [ { "id": 1158, "type": "entrada", "quantity": 17, ... } ]
  }
}
```

### Uso rápido

1. Instalar la extensión [REST Client](https://marketplace.visualstudio.com/items?itemName=humao.rest-client) en VS Code.
2. Abrir cualquier archivo `.http` de la carpeta `rest.client/`.
3. Hacer clic en **Send Request** sobre la petición deseada.
4. La variable `@token` ya está pre-configurada con el token del usuario semilla.
