# Instrucciones para GitHub Copilot

## Idioma y Nomenclatura

- **Respuestas y comentarios**: Siempre en español, independiente del idioma de la pregunta
- **Comentarios en código**: SIEMPRE en español, explicativos y claros
- **Nombres en código**: ÚNICAMENTE en inglés (variables, funciones, clases, métodos)
- **Convenciones**:
  - JavaScript/Vue: `camelCase` para variables y funciones, `PascalCase` para componentes
  - PHP/Laravel: `snake_case` para métodos y propiedades, `PascalCase` para clases
  - Base de datos: `snake_case` para tablas y columnas
- **SIEMPRE** usa `use` al inicio del archivo para importar clases. **NUNCA** escribas el namespace completo inline dentro del código (`\Modules\Foo\Bar\Baz::method()`).

## Stack Tecnológico

- **Backend**: Laravel 11.x (API RESTful) - En migración desde Laravel 8
- **PHP**: 8.2 o superior (requerido para Laravel 11)
- **Base de datos**: MySQL/MariaDB
- **Características Laravel**: Eloquent ORM, Jobs, Notifications, Helpers personalizados
- **Autenticación**: Token legacy para consumo de API externa (será modernizado según progreso de migración)

## Contexto del Proyecto

API RESTful de gestión de inventario y productos en **PROCESO DE MIGRACIÓN A LARAVEL 11**. Sistema que proporciona endpoints para:
- Gestión de categorías de productos
- Administración de productos (creación, actualización, consulta)
- Control de stock con movimientos de entrada/salida
- Registro de movimientos de stock asociados a usuarios
- Autenticación mediante token legacy para clientes externos

### Estado de la Migración

**CRÍTICO**: Este proyecto está en transición de Laravel 8 a Laravel 11. El enfoque es:
- Reemplazar y modernizar código legacy que sea incompatible o genere inconsistencias en Laravel 11+
- Implementar nuevas prácticas y patrones de Laravel 11
- Eliminar código deprecado o que no siga los estándares de Laravel 11
- Asegurar compatibilidad con PHP 8.2+
- Mantener la funcionalidad de la API sin quebrar consumidores externos

## Arquitectura Core del Sistema

### Estructura Relacional (CRÍTICO)

El sistema modela una jerarquía de tres niveles para la gestión de inventario:

Diagrama lógico:

```
User (tabla: users)
    ↓ registra movimientos
StockMovement (tabla: stock_movements)
    ↓ afecta a
Product (tabla: products)
    ↓ pertenece a
Category (tabla: categories)
```

Relaciones y responsabilidades:

- **Category**: Agrupa productos por categoría. Campos: `name`, `description`, `status`.
- **Product**: Producto con precio y stock. Campos: `name`, `description`, `price`, `stock`, `category_id`, `status`.
- **StockMovement**: Registra entrada/salida de productos. Campos: `product_id`, `type`, `quantity`, `reason`, `user_id`.
- **User**: Usuario que registra movimientos. Campos: `name`, `email`, `api_token`. Mantiene token legacy para autenticación.

Flujo de operaciones:

- Los movimientos de stock se crean asociados a un producto y usuario específico.
- El campo `stock` en products se actualiza según los movimientos registrados.
- Los movimientos deben ser auditables: siempre rastrear qué usuario y cuándo se registró cada movimiento.
- El campo `type` en stock_movements indica si es entrada (entrada) o salida (salida).

Implicaciones al modificar datos:

- Un cambio en un movimiento de stock debe reflejar consistencia en el campo `stock` del producto.
- Validar que no haya movimientos que dejen el stock en valores negativos (según la lógica de negocio).
- Mantener la integridad referencial: cada movimiento debe tener producto y usuario válidos.

### Helpers Críticos

- **Validaciones robustas en StockMovement**: Verificar disponibilidad de stock antes de salidas
- **Cálculos consistentes**: Agregación de movimientos para reportes de inventario

### Modelos Principales

- `User`: Usuarios autenticados con token legacy
- `Category`: Categorías de productos
- `Product`: Productos con relación a categorías y movimientos de stock
- `StockMovement`: Registro de cambios de inventario asociados a usuarios

## Mejores Prácticas

### General

- Nivel de código: Semi-senior a Senior
- Código limpio, SOLID, DRY
- Validaciones robustas en Requests
- Manejo de errores consistente con try-catch y respuestas JSON estandarizadas
- en clases PHP, prefiere use Clase; en lugar de use App\Namespace\Clase; para mejorar legibilidad

### Backend (Laravel 11)

**TRABAJAR SIEMPRE CONSIDERANDO LARAVEL 11 COMO VERSIÓN OBJETIVO**

- **Attributes**: Usar lazy-loaded properties en lugar de accessors/mutators legacy cuando sea posible
- **Resources**: Usar para transformar respuestas API (pattern establecido)
- **Jobs & Queues**: Jobs para tareas asíncronas con queue drivers modernos
- **Eager Loading**: Prevenir N+1 queries obligatoriamente
- **Model Scopes**: Queries reutilizables en modelos
- **Form Requests**: Validación centralizada, lógica en Controllers
- **Routing**: Usar routing actualizado sin restricciones legacy
- **Type Hints**: Utilizar type hints completos en todos los métodos (requisito PHP 8.2)
- **Constructor Property Promotion**: Aplicar cuando sea apropiado
- **Named Arguments**: Usar cuando mejore legibilidad
- **Enum Support**: Aplicar Enums nativos de PHP para campos como `type` en stock_movements
- **CRÍTICO**: Al modificar stock, considerar impacto en integridad de datos e inventario
- **CRÍTICO**: Eliminar código legacy de Laravel 8 que genere inconsistencias en Laravel 11+

### Base de Datos

- Migraciones versionadas y reversibles
- Índices en columnas de búsqueda frecuente
- Soft deletes cuando corresponda
- Timestamps en todas las tablas

## Reglas de Modificación

### Generales

- **NO modificar código de commits anteriores** sin solicitud explícita
- Mantener consistencia en respuestas API (estructura, códigos HTTP)
- Documentar cambios complejos con comentarios en español
- Validar integridad referencial en todas las operaciones (especialmente stock_movements)
- Testear cambios que afecten el control de inventario

### Específicas para Migración Laravel 11

- **Reemplazar código legacy**: Identificar y reemplazar patrones de Laravel 8 que no se alineen con Laravel 11
- **PHP 8.2+**: Todo código nuevo debe ser compatible con PHP 8.2 o superior
- **Type Safety**: Implementar type hints completos en parámetros y retornos
- **Modernizar**: Preferir características nativas de PHP 8.2+ (match expressions, named arguments, etc.)
- **Eliminar Deprecadas**: No usar métodos o funciones deprecadas en Laravel 11
- **Documentar Cambios**: Especificar en comentarios cuando se reemplace código legacy
- **Compatibilidad de API**: Los cambios internos NO deben afectar los endpoints disponibles para clientes externos
