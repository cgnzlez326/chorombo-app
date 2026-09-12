<?php

declare(strict_types=1);

use App\Controllers\DocumentoController;
use App\Controllers\TipoDocumentoController;
use App\Core\Cors;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\FileStorage;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Router;
use App\Core\Validator;
use App\Exceptions\TooManyRequestsException;
use App\Repositories\DocumentoRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Services\DocumentoService;
use App\Services\TipoDocumentoService;

/**
 * Arranque de la aplicación y wiring de dependencias.
 * Crea repositorios, servicios y controladores (inyectando interfaces), aplica CORS,
 * ejecuta el rate limit según el método HTTP y devuelve el router listo para despachar.
 */
require __DIR__ . '/autoload.php';

$config = require __DIR__ . '/../config/config.php';

ErrorHandler::register();
Cors::apply($config['cors']);

$database = new Database($config['db']);
$tipoDocumentoRepository = new TipoDocumentoRepository($database);
$documentoRepository = new DocumentoRepository($database);

$fileStorage = new FileStorage(
    $config['uploads']['directory'],
    $config['uploads']['max_size'],
    $config['uploads']['allowed_extensions'],
);

// Los controladores reciben sus dependencias ya construidas: los servicios dependen de
// interfaces (DIP) y la base de datos implementa tanto PDO como el gestor de transacciones.
$controllers = [
    'documento'     => new DocumentoController(
        new DocumentoService(
            $documentoRepository,
            $tipoDocumentoRepository,
            new Validator(),
            $fileStorage,
            $database,
        ),
        $config['pagination']['per_page'],
        $config['pagination']['max_per_page'],
    ),
    'tipoDocumento' => new TipoDocumentoController(new TipoDocumentoService($tipoDocumentoRepository)),
];

$request = Request::capture($config['uploads']['max_request_size']);

// El rate limit se aplica antes del router, con límites distintos para lectura y escritura.
if ($config['rate_limit']['enabled']) {
    $rateLimit = $config['rate_limit'];
    $isWrite = in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    $limiter = new RateLimiter($rateLimit['directory']);

    $result = $limiter->attempt(
        sprintf('%s:%s', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $isWrite ? 'write' : 'read'),
        $isWrite ? $rateLimit['write_max_requests'] : $rateLimit['max_requests'],
        $rateLimit['window'],
    );

    if (!$result['allowed']) {
        throw new TooManyRequestsException($result['retry_after']);
    }
}

return [
    'router'      => new Router(),
    'request'     => $request,
    'controllers' => $controllers,
];
