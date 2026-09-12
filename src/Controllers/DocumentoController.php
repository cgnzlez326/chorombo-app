<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\DocumentoService;

class DocumentoController
{
    public function __construct(private readonly DocumentoService $service)
    {
    }

    public function index(Request $request): void
    {
        $documentos = array_map(
            static fn ($documento) => $documento->toArray(),
            $this->service->list($this->tipoDocumentoFilter($request)),
        );

        Response::collection($documentos);
    }

    public function show(Request $request, array $params): void
    {
        Response::success($this->service->get((int) $params['id'])->toArray());
    }

    public function store(Request $request): void
    {
        $documento = $this->service->create($request->body, $request->file('archivo'));

        Response::success($documento->toArray(), 'Documento creado correctamente.', 201);
    }

    public function update(Request $request, array $params): void
    {
        $documento = $this->service->update(
            (int) $params['id'],
            $request->body,
            $request->file('archivo'),
        );

        Response::success($documento->toArray(), 'Documento actualizado correctamente.');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->service->delete((int) $params['id']);

        Response::success(null, 'Documento eliminado correctamente.');
    }

    public function download(Request $request, array $params): void
    {
        $file = $this->service->file((int) $params['id']);

        Response::file($file['path'], $file['name']);
    }

    private function tipoDocumentoFilter(Request $request): ?int
    {
        $value = $request->query['tipo_documento_id'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new ValidationException(['tipo_documento_id' => ['Debe ser un número entero.']]);
        }

        return (int) $value;
    }
}
