<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): never
    {
        $payload = ['success' => true, 'data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        self::json($payload, $status);
    }

    public static function collection(array $items, ?int $total = null): never
    {
        self::json([
            'success' => true,
            'data'    => $items,
            'total'   => $total ?? count($items),
        ], 200);
    }

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

    public static function file(string $path, string $downloadName): never
    {
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($downloadName) . '"');
        header('Content-Length: ' . (string) filesize($path));

        readfile($path);
        exit;
    }

    private static function json(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'archivo';
    }
}
