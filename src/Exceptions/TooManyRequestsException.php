<?php

declare(strict_types=1);

namespace App\Exceptions;

class TooManyRequestsException extends HttpException
{
    public function __construct(private readonly int $retryAfter)
    {
        parent::__construct(
            429,
            'Demasiadas solicitudes. Intente nuevamente más tarde.',
            'RATE_LIMITED',
        );
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}
