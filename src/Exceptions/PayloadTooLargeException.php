<?php

declare(strict_types=1);

namespace App\Exceptions;

class PayloadTooLargeException extends HttpException
{
    public function __construct(int $maxBytes)
    {
        parent::__construct(
            413,
            sprintf(
                'El cuerpo de la solicitud supera el máximo permitido de %d MB.',
                intdiv($maxBytes, 1024 * 1024),
            ),
            'PAYLOAD_TOO_LARGE',
        );
    }
}
