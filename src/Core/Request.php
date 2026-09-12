<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\PayloadTooLargeException;

/**
 * Representa la petición HTTP actual ya normalizada (método, path, query, body y archivos).
 * Se construye una sola vez con `capture()` y abstrae las diferencias entre POST y PUT/PATCH.
 */
class Request
{
    private static array $temporaryFiles = [];

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
    ) {
    }

    /** Lee superglobales y cuerpo de la petición; registra la limpieza de archivos temporales al terminar. */
    public static function capture(int $maxRequestSize = 0): self
    {
        self::$temporaryFiles = [];
        register_shutdown_function([self::class, 'cleanupTemporaryFiles']);

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        [$body, $files] = self::parsePayload($method, $maxRequestSize);

        return new self(
            method: $method,
            path: self::resolvePath(),
            query: $_GET ?? [],
            body: $body,
            files: $files,
        );
    }

    public static function cleanupTemporaryFiles(): void
    {
        foreach (self::$temporaryFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /** Devuelve un campo del cuerpo o el valor por defecto. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /** Devuelve la descripción del archivo subido o null si no se envió ninguno. */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /**
     * PHP solo rellena $_POST y $_FILES en POST; para PUT/PATCH se parsea el cuerpo.
     *
     * @return array{0: array, 1: array}
     */
    private static function parsePayload(string $method, int $maxRequestSize): array
    {
        if ($method === 'POST') {
            self::assertRequestSize($maxRequestSize);

            return [$_POST ?? [], $_FILES ?? []];
        }

        if (!in_array($method, ['PUT', 'PATCH'], true)) {
            return [[], []];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = self::readBody($maxRequestSize);

        if ($raw === '') {
            return [[], []];
        }

        if (preg_match('#boundary=(.+)$#i', $contentType, $matches)) {
            return self::parseMultipart($raw, trim($matches[1], '"'));
        }

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);

            return [is_array($decoded) ? $decoded : [], []];
        }

        parse_str($raw, $fields);

        return [$fields, []];
    }

    /** Corta la petición con 413 si Content-Length supera el máximo configurado. */
    private static function assertRequestSize(int $maxRequestSize): void
    {
        if ($maxRequestSize <= 0) {
            return;
        }

        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxRequestSize) {
            throw new PayloadTooLargeException($maxRequestSize);
        }
    }

    /** Lee `php://input` validando también el tamaño real del cuerpo. */
    private static function readBody(int $maxRequestSize): string
    {
        self::assertRequestSize($maxRequestSize);

        $raw = file_get_contents('php://input');

        if ($raw === false) {
            return '';
        }

        if ($maxRequestSize > 0 && strlen($raw) > $maxRequestSize) {
            throw new PayloadTooLargeException($maxRequestSize);
        }

        return $raw;
    }

    /**
     * @return array{0: array, 1: array}
     */
    private static function parseMultipart(string $raw, string $boundary): array
    {
        $fields = [];
        $files = [];

        foreach (explode("\r\n--" . $boundary, "\r\n" . $raw) as $part) {
            $part = ltrim($part, "\r\n");

            if ($part === '' || str_starts_with($part, '--')) {
                continue;
            }

            $separator = strpos($part, "\r\n\r\n");

            if ($separator === false) {
                continue;
            }

            $headers = substr($part, 0, $separator);
            $content = substr($part, $separator + 4);

            if (str_ends_with($content, "\r\n")) {
                $content = substr($content, 0, -2);
            }

            if (!preg_match('#name="([^"]*)"#i', $headers, $nameMatch)) {
                continue;
            }

            $name = $nameMatch[1];

            if (preg_match('#filename="([^"]*)"#i', $headers, $fileMatch) && $fileMatch[1] !== '') {
                self::assign($files, $name, self::temporaryFile($fileMatch[1], $headers, $content));
                continue;
            }

            self::assign($fields, $name, $content);
        }

        return [$fields, $files];
    }

    /** Asigna el valor respetando la convención de campos repetidos `campo[]`. */
    private static function assign(array &$target, string $name, mixed $value): void
    {
        if (str_ends_with($name, '[]')) {
            $target[substr($name, 0, -2)][] = $value;

            return;
        }

        $target[$name] = $value;
    }

    /** Escribe el contenido de una parte multipart en un temporal y lo devuelve con formato de $_FILES. */
    private static function temporaryFile(string $originalName, string $headers, string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'chorombo_');

        if ($path === false) {
            return ['name' => $originalName, 'error' => UPLOAD_ERR_CANT_WRITE, 'tmp_name' => '', 'size' => 0];
        }

        self::$temporaryFiles[] = $path;
        file_put_contents($path, $content);

        $contentType = preg_match('#Content-Type:\s*(.+)#i', $headers, $match) ? trim($match[1]) : '';

        return [
            'name'     => $originalName,
            'type'     => $contentType,
            'tmp_name' => $path,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($content),
        ];
    }

    /** Obtiene el path de la URI quitando el directorio base donde se sirve la app. */
    private static function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

    /** Deduce el prefijo base (ej. `/chorombo-app`) a partir de SCRIPT_NAME. */
    private static function basePath(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = dirname($script, 2);

        if ($base === '/' || $base === '.' || $base === '\\') {
            return '';
        }

        return rtrim($base, '/');
    }
}
