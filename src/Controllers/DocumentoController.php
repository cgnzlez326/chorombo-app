<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\DocumentoService;

class DocumentoController
{
    public function __construct(
        private readonly DocumentoService $service,
        private readonly int $defaultPerPage = 20,
        private readonly int $maxPerPage = 100,
    ) {
    }

    public function index(Request $request): void
    {
        $page = $this->queryInt($request, 'page', 1);
        $perPage = $this->queryInt($request, 'per_page', $this->defaultPerPage);

        if ($page < 1) {
            throw new ValidationException(['page' => ['Debe ser mayor o igual a 1.']]);
        }

        if ($perPage < 1 || $perPage > $this->maxPerPage) {
            throw new ValidationException([
                'per_page' => [sprintf('Debe estar entre 1 y %d.', $this->maxPerPage)],
            ]);
        }

        $result = $this->service->list($this->tipoDocumentoFilter($request), $page, $perPage);

        Response::collection(
            array_map(static fn ($documento) => $documento->toArray(), $result['items']),
            $result['total'],
            $result['page'],
            $result['per_page'],
        );
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

    private function queryInt(Request $request, string $key, int $default): int
    {
        $value = $request->query[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new ValidationException([$key => ['Debe ser un número entero.']]);
        }

        return (int) $value;
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
