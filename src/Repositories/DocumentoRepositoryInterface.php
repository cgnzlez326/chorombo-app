<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Documento;

interface DocumentoRepositoryInterface
{
    /** @return Documento[] */
    public function paginate(?int $tipoDocumentoId, int $limit, int $offset): array;

    public function count(?int $tipoDocumentoId = null): int;

    public function find(int $id): ?Documento;

    public function findByArchivoHash(string $hash, ?int $excludeId = null): ?Documento;

    public function create(Documento $documento): int;

    public function update(int $id, Documento $documento): void;

    public function delete(int $id): void;
}
