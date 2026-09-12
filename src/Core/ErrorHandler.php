<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_exception_handler(static function (Throwable $exception): void {
            if ($exception instanceof HttpException) {
                Response::error(
                    $exception->errorCode(),
                    $exception->getMessage(),
                    $exception->status(),
                    $exception->details(),
                );
            }

            error_log(sprintf(
                '[chorombo-api] %s en %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            Response::error('INTERNAL_ERROR', 'Ocurrió un error interno en el servidor.', 500);
        });
    }
}
