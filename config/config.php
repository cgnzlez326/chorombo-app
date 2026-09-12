<?php

declare(strict_types=1);

/**
 * Configuración central de la API: base de datos, paginación, rate limit, CORS y subidas.
 * Todos los valores se leen en src/bootstrap.php.
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'chorombo',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'pagination' => [
        'per_page'     => 20,
        'max_per_page' => 100,
    ],

    'rate_limit' => [
        'enabled'            => true,
        'directory'          => __DIR__ . '/../storage/cache/ratelimit',
        'window'             => 60,
        'max_requests'       => 120,
        'write_max_requests' => 60,
    ],

    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'max_age'         => 86400,
    ],

    'uploads' => [
        'directory'          => __DIR__ . '/../storage/uploads',
        'max_size'           => 5 * 1024 * 1024,
        'max_request_size'   => 6 * 1024 * 1024,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'],
    ],
];
