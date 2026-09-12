<?php

declare(strict_types=1);

namespace App\Exceptions;

/** Error 409 cuando ya existe un documento con el mismo archivo (mismo hash). */
class DuplicateException extends HttpException
{
    public function __construct(string $message = 'Ya existe un documento con el mismo archivo.', ?array $details = null)
    {
        parent::__construct(409, $message, 'DUPLICATE_DOCUMENT', $details);
    }
}
