<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;

function fakeRequest(string $method, string $uri): Request
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/chorombo-app/public/index.php';
    $_GET = [];

    return Request::capture();
}

test('Router ejecuta el handler con los parámetros de ruta', function (): void {
    $captured = null;
    $router = new Router();
    $router->get('/api/documentos/{id}', static function (Request $request, array $params) use (&$captured): void {
        $captured = $params['id'];
    });

    $router->dispatch(fakeRequest('GET', '/chorombo-app/api/documentos/7'));

    assertSame('7', $captured);
});

test('Router lanza 404 en ruta desconocida', function (): void {
    $router = new Router();

    assertThrows(NotFoundException::class, static function () use ($router): void {
        $router->dispatch(fakeRequest('GET', '/api/desconocida'));
    });
});

test('Router lanza 405 si el método no coincide', function (): void {
    $router = new Router();
    $router->get('/api/documentos', static function (): void {
    });

    assertThrows(HttpException::class, static function () use ($router): void {
        $router->dispatch(fakeRequest('POST', '/api/documentos'));
    });
});
