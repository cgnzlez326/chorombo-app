<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;

/** @var array{router: Router, request: Request, controllers: array} $app */
$router = $app['router'];
$documentoController = $app['controllers']['documento'];
$tipoDocumentoController = $app['controllers']['tipoDocumento'];

$router->get('/api/documentos', static fn (Request $request) => $documentoController->index($request));
$router->get('/api/documentos/{id}', static fn (Request $request, array $params) => $documentoController->show($request, $params));
$router->post('/api/documentos', static fn (Request $request) => $documentoController->store($request));
$router->put('/api/documentos/{id}', static fn (Request $request, array $params) => $documentoController->update($request, $params));
$router->patch('/api/documentos/{id}', static fn (Request $request, array $params) => $documentoController->update($request, $params));
$router->delete('/api/documentos/{id}', static fn (Request $request, array $params) => $documentoController->destroy($request, $params));
$router->get('/api/documentos/{id}/archivo', static fn (Request $request, array $params) => $documentoController->download($request, $params));

$router->get('/api/tipos-documento', static fn (Request $request) => $tipoDocumentoController->index($request));
