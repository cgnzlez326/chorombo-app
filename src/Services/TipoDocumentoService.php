<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TipoDocumento;
use App\Repositories\TipoDocumentoRepositoryInterface;

/** Lógica de negocio del catálogo de tipos de documento. */
class TipoDocumentoService
{
    public function __construct(private readonly TipoDocumentoRepositoryInterface $repository)
    {
    }

    /** @return TipoDocumento[] */
    public function list(): array
    {
        return $this->repository->all();
    }
}
