<?php

declare(strict_types=1);

namespace App\Exceptions;

/** Error 404 para recursos o rutas inexistentes. */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Recurso no encontrado.')
    {
        parent::__construct(404, $message, 'NOT_FOUND');
    }
}
