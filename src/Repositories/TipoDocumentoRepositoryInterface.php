<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TipoDocumento;

/** Contrato de lectura del catálogo de tipos de documento. */
interface TipoDocumentoRepositoryInterface
{
    /** @return TipoDocumento[] */
    public function all(): array;

    public function exists(int $id): bool;
}
