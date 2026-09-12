<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Emisor de respuestas HTTP en el formato JSON acordado con el Frontend.
 * Todos los métodos terminan la ejecución (`exit`), por eso declaran `never`.
 */
class Response
{
    /** Respuesta de éxito: `{ success: true, data, message? }`. */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): never
    {
        $payload = ['success' => true, 'data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        self::json($payload, $status);
    }

    /** Listado paginado: agrega `total` y, si hay página, `page`, `per_page` y `last_page`. */
    public static function collection(array $items, ?int $total = null, ?int $page = null, ?int $perPage = null): never
    {
        $payload = [
            'success' => true,
            'data'    => $items,
            'total'   => $total ?? count($items),
        ];

        if ($page !== null && $perPage !== null && $perPage > 0) {
            $payload['page']      = $page;
            $payload['per_page']  = $perPage;
            $payload['last_page'] = (int) ceil($payload['total'] / $perPage);
        }

        self::json($payload, 200);
    }

    /** Respuesta de error: `{ success: false, error: { code, message, details? } }`. */
    public static function error(string $code, string $message, int $status, ?array $details = null): never
    {
        $error = [
            'code'    => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        self::json(['success' => false, 'error' => $error], $status);
    }

    /** Descarga el archivo indicado forzando el nombre original como attachment. */
    public static function file(string $path, string $downloadName): never
    {
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($downloadName) . '"');
        header('Content-Length: ' . (string) filesize($path));

        readfile($path);
        exit;
    }

    /** Envía el payload como JSON UTF-8 y termina la petición. */
    private static function json(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Limpia el nombre de archivo para que sea seguro en la cabecera Content-Disposition. */
    private static function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'archivo';
    }
}
