<?php

declare(strict_types=1);

namespace App\Core;

/** Contrato de almacenamiento de archivos; permite sustituirlo por dobles en pruebas. */
interface FileStorageInterface
{
    /** @return array{filename: string, original_name: string, hash: string} */
    public function store(array $file): array;

    public function delete(?string $filename): void;

    public function exists(string $filename): bool;

    public function path(string $filename): string;
}
