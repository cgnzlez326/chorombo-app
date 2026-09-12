<?php

declare(strict_types=1);

/**
 * Front controller: único punto de entrada de la API.
 * Arranca el bootstrap (config, dependencias, rate limit), carga las rutas y despacha la petición.
 */
$app = require __DIR__ . '/../src/bootstrap.php';

require __DIR__ . '/../routes/api.php';

$app['router']->dispatch($app['request']);
