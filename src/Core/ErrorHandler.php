<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Exceptions\TooManyRequestsException;
use ErrorException;
use Throwable;

/**
 * Centraliza el manejo de errores: convierte errores PHP en excepciones, responde
 * HttpException con su código/mensaje y todo lo demás como 500, además de registrar en el log.
 */
class ErrorHandler
{
    private const FATAL_ERRORS = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    /** Registra los handlers de errores, excepciones no capturadas y errores fatales. */
    public static function register(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception): void {
            if ($exception instanceof HttpException) {
                if ($exception instanceof TooManyRequestsException) {
                    header('Retry-After: ' . $exception->retryAfter());
                }

                Response::error(
                    $exception->errorCode(),
                    $exception->getMessage(),
                    $exception->status(),
                    $exception->details(),
                );
            }

            self::log($exception);

            Response::error('INTERNAL_ERROR', 'Ocurrió un error interno en el servidor.', 500);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();

            if ($error === null || !in_array($error['type'], self::FATAL_ERRORS, true)) {
                return;
            }

            self::log(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));

            if (!headers_sent()) {
                Response::error('INTERNAL_ERROR', 'Ocurrió un error interno en el servidor.', 500);
            }
        });
    }

    /** Escribe la excepción en el log de PHP sin exponer detalles al cliente. */
    private static function log(Throwable $exception): void
    {
        error_log(sprintf(
            '[chorombo-api] %s en %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        ));
    }
}
