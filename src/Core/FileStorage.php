<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidationException;
use RuntimeException;

/**
 * Almacenamiento de archivos subidos en disco, con validación de extensión y MIME real.
 * Genera un nombre aleatorio para el archivo y calcula su hash SHA-256 para detectar duplicados.
 */
class FileStorage implements FileStorageInterface
{
    private const MIME_TYPES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
        ],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
    ];

    public function __construct(
        private readonly string $directory,
        private readonly int $maxSize,
        private readonly array $allowedExtensions,
    ) {
    }

    /** Valida y guarda el archivo; devuelve `filename`, `original_name` y `hash`. */
    public function store(array $file): array
    {
        $this->assertUploaded($file);

        $originalName = basename((string) ($file['name'] ?? 'archivo'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new ValidationException([
                'archivo' => ['Extensión no permitida. Permitidas: ' . implode(', ', $this->allowedExtensions) . '.'],
            ]);
        }

        if (!$this->mimeIsAllowed($file['tmp_name'], $extension)) {
            throw new ValidationException(['archivo' => ['El contenido del archivo no corresponde a su extensión.']]);
        }

        $this->ensureDirectory();

        $hash = hash_file('sha256', $file['tmp_name']);

        if ($hash === false) {
            throw new RuntimeException('No se pudo calcular la huella del archivo.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $this->path($filename);

        if (!$this->move($file['tmp_name'], $target)) {
            throw new RuntimeException('No se pudo almacenar el archivo en el servidor.');
        }

        return [
            'filename'      => $filename,
            'original_name' => $originalName,
            'hash'          => $hash,
        ];
    }

    /** Elimina el archivo si existe; ignora nombres nulos o vacíos. */
    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $this->path($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /** Indica si el archivo existe en el directorio de subidas. */
    public function exists(string $filename): bool
    {
        return is_file($this->path($filename));
    }

    /** Ruta absoluta del archivo, forzando basename para evitar traversal. */
    public function path(string $filename): string
    {
        return rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . basename($filename);
    }

    /** Verifica que la subida terminó sin errores y que el tamaño está dentro del máximo. */
    private function assertUploaded(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException(['archivo' => [$this->uploadErrorMessage($error)]]);
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > $this->maxSize) {
            throw new ValidationException([
                'archivo' => [sprintf('El archivo debe pesar entre 1 byte y %d MB.', intdiv($this->maxSize, 1024 * 1024))],
            ]);
        }
    }

    /** Comprueba que el MIME detectado del contenido corresponda a la extensión declarada. */
    private function mimeIsAllowed(string $tmpPath, string $extension): bool
    {
        $detected = mime_content_type($tmpPath) ?: '';
        $allowed = self::MIME_TYPES[$extension] ?? [];

        return in_array($detected, $allowed, true);
    }

    /** Mueve el archivo soportando subidas HTTP y temporales creados por Request (rename/copy). */
    private function move(string $source, string $target): bool
    {
        if (is_uploaded_file($source)) {
            return move_uploaded_file($source, $target);
        }

        if (@rename($source, $target)) {
            return true;
        }

        if (@copy($source, $target)) {
            @unlink($source);

            return true;
        }

        return false;
    }

    /** Crea el directorio de subidas si aún no existe. */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }

    /** Traduce un código UPLOAD_ERR_* a un mensaje legible. */
    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL                         => 'El archivo se recibió de forma incompleta.',
            default                                    => 'No se pudo recibir el archivo.',
        };
    }
}
