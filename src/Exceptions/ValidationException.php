<?php

declare(strict_types=1);

namespace App\Exceptions;

/** Error 422 con el detalle de los campos inválidos. */
class ValidationException extends HttpException
{
    public function __construct(array $details, string $message = 'Los datos enviados no son válidos.')
    {
        parent::__construct(422, $message, 'VALIDATION_ERROR', $details);
    }
}
