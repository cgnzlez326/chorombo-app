<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rate limiting por ventana fija con almacenamiento en archivos y bloqueo `flock`.
 * No requiere Redis ni APCu: cada clave se guarda como JSON en el directorio indicado.
 */
class RateLimiter
{
    public function __construct(private readonly string $directory)
    {
    }

    /**
     * Suma un intento para $key y responde si sigue permitido y cuándo reintentar.
     *
     * @return array{allowed: bool, retry_after: int}
     */
    public function attempt(string $key, int $maxRequests, int $windowSeconds): array
    {
        if ($maxRequests <= 0 || $windowSeconds <= 0) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        $this->ensureDirectory();

        $handle = fopen($this->path($key), 'c+');

        if ($handle === false) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return ['allowed' => true, 'retry_after' => 0];
            }

            $now = time();
            $state = $this->read($handle, $now, $windowSeconds);

            if ($state['reset_at'] <= $now) {
                $state = ['count' => 0, 'reset_at' => $now + $windowSeconds];
            }

            $state['count']++;
            $allowed = $state['count'] <= $maxRequests;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);

            return [
                'allowed'     => $allowed,
                'retry_after' => $allowed ? 0 : max(1, $state['reset_at'] - $now),
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Lee el estado guardado o devuelve una ventana nueva si el archivo es inválido.
     *
     * @return array{count: int, reset_at: int}
     */
    private function read($handle, int $now, int $windowSeconds): array
    {
        $decoded = json_decode((string) stream_get_contents($handle), true);

        if (!is_array($decoded) || !isset($decoded['count'], $decoded['reset_at'])) {
            return ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }

        return ['count' => (int) $decoded['count'], 'reset_at' => (int) $decoded['reset_at']];
    }

    /** Ruta del archivo de estado: hash SHA-256 de la clave para evitar nombres inseguros. */
    private function path(string $key): string
    {
        return rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }

    /** Crea el directorio de estado si aún no existe. */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }
}
