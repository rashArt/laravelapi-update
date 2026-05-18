<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rutas cubiertas por CORS
    |--------------------------------------------------------------------------
    | Se aplica a todas las rutas del API. El patrón 'api/*' cubre todos los
    | endpoints definidos en routes/api.php.
    */
    'paths' => ['api/*', 'docs/api', 'docs/api.json'],

    /*
    |--------------------------------------------------------------------------
    | Métodos HTTP permitidos
    |--------------------------------------------------------------------------
    | Permite todos los métodos utilizados por los endpoints REST del proyecto.
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos
    |--------------------------------------------------------------------------
    | '*' permite el consumo desde cualquier origen externo.
    | Para restringir a dominios específicos, reemplazar por un array de URLs.
    */
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Cabeceras permitidas
    |--------------------------------------------------------------------------
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Cabeceras expuestas al cliente
    |--------------------------------------------------------------------------
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de caché del preflight (segundos)
    |--------------------------------------------------------------------------
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Soporte de credenciales
    |--------------------------------------------------------------------------
    | Debe ser false cuando allowed_origins es '*'. Activar solo si se usan
    | cookies o sesiones entre origen y API.
    */
    'supports_credentials' => false,
];
