<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        [$body, $files] = self::parsePayload($method);

        return new self(
            method: $method,
            path: self::resolvePath(),
            query: $_GET ?? [],
            body: $body,
            files: $files,
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

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
    private static function parsePayload(string $method): array
    {
        if ($method === 'POST') {
            return [$_POST ?? [], $_FILES ?? []];
        }

        if (!in_array($method, ['PUT', 'PATCH'], true)) {
            return [[], []];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (preg_match('#boundary=(.+)$#i', $contentType, $matches)) {
            $raw = file_get_contents('php://input');

            return $raw === false ? [[], []] : self::parseMultipart($raw, trim($matches[1], '"'));
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [[], []];
        }

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);

            return [is_array($decoded) ? $decoded : [], []];
        }

        parse_str($raw, $fields);

        return [$fields, []];
    }

    /**
     * @return array{0: array, 1: array}
     */
    private static function parseMultipart(string $raw, string $boundary): array
    {
        $fields = [];
        $files = [];

        foreach (explode('--' . $boundary, $raw) as $part) {
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
                $files[$name] = self::temporaryFile($fileMatch[1], $headers, $content);
                continue;
            }

            $fields[$name] = $content;
        }

        return [$fields, $files];
    }

    private static function temporaryFile(string $originalName, string $headers, string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'chorombo_');

        if ($path === false) {
            return ['name' => $originalName, 'error' => UPLOAD_ERR_CANT_WRITE, 'tmp_name' => '', 'size' => 0];
        }

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

    private static function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

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
