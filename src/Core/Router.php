<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;

/**
 * Enrutador mínimo: registra rutas por método HTTP y las resuelve contra el path del Request.
 * Los placeholders `{param}` se convierten en grupos con nombre y se pasan al handler como array.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    /** Busca la ruta que coincide; lanza 405 si el path existe con otro método y 404 si no existe. */
    public function dispatch(Request $request): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($request, $params);

            return;
        }

        if ($pathMatched) {
            throw new HttpException(405, 'Método HTTP no permitido para esta ruta.', 'METHOD_NOT_ALLOWED');
        }

        throw new NotFoundException('La ruta solicitada no existe.');
    }

    /** Registra una ruta compilando su path a una expresión regular. */
    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->compile($path),
            'handler' => $handler,
        ];
    }

    /** Convierte `/api/documentos/{id}` en `#^/api/documentos/(?P<id>[^/]+)$#`. */
    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);

        return '#^' . $pattern . '$#';
    }
}
