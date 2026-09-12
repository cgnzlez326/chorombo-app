<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción base de errores HTTP: transporta status, código de error y detalles opcionales
 * que ErrorHandler usa para construir la respuesta JSON de error.
 */
class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $status,
        string $message,
        private readonly string $errorCode = 'HTTP_ERROR',
        private readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): ?array
    {
        return $this->details;
    }
}
