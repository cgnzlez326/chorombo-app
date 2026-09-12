<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TipoDocumentoService;

class TipoDocumentoController
{
    public function __construct(private readonly TipoDocumentoService $service)
    {
    }

    public function index(Request $request): void
    {
        $tipos = array_map(
            static fn ($tipo) => $tipo->toArray(),
            $this->service->list(),
        );

        Response::collection($tipos);
    }
}
