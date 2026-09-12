<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\FileStorage;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Documento;
use App\Repositories\DocumentoRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;

class DocumentoService
{
    private const RULES = [
        'titulo'            => ['required', 'string', 'max:255'],
        'tipo_documento_id' => ['required', 'integer'],
        'fecha'             => ['required', 'date'],
        'descripcion'       => ['nullable', 'string', 'max:2000'],
    ];

    public function __construct(
        private readonly DocumentoRepositoryInterface $repository,
        private readonly TipoDocumentoRepositoryInterface $tipoDocumentoRepository,
        private readonly Validator $validator,
        private readonly FileStorage $fileStorage,
    ) {
    }

    /** @return Documento[] */
    public function list(?int $tipoDocumentoId = null): array
    {
        return $this->repository->all($tipoDocumentoId);
    }

    public function get(int $id): Documento
    {
        $documento = $this->repository->find($id);

        if ($documento === null) {
            throw new NotFoundException('El documento solicitado no existe.');
        }

        return $documento;
    }

    public function create(array $data, ?array $file): Documento
    {
        $validated = $this->validator->validate($data, self::RULES);
        $this->assertTipoDocumento((int) $validated['tipo_documento_id']);

        $archivo = null;
        $archivoNombre = null;

        if ($file !== null) {
            $stored = $this->fileStorage->store($file);
            $archivo = $stored['filename'];
            $archivoNombre = $stored['original_name'];
        }

        $id = $this->repository->create(new Documento(
            titulo: $validated['titulo'],
            tipoDocumentoId: (int) $validated['tipo_documento_id'],
            fecha: $validated['fecha'],
            descripcion: $validated['descripcion'] ?? null,
            archivo: $archivo,
            archivoNombreOriginal: $archivoNombre,
        ));

        return $this->get($id);
    }

    public function update(int $id, array $data, ?array $file): Documento
    {
        $current = $this->get($id);

        $merged = [
            'titulo'            => $data['titulo'] ?? $current->titulo,
            'tipo_documento_id' => $data['tipo_documento_id'] ?? $current->tipoDocumentoId,
            'fecha'             => $data['fecha'] ?? $current->fecha,
            'descripcion'       => array_key_exists('descripcion', $data) ? $data['descripcion'] : $current->descripcion,
        ];

        $validated = $this->validator->validate($merged, self::RULES);
        $this->assertTipoDocumento((int) $validated['tipo_documento_id']);

        $archivo = $current->archivo;
        $archivoNombre = $current->archivoNombreOriginal;

        if ($file !== null) {
            $stored = $this->fileStorage->store($file);
            $this->fileStorage->delete($current->archivo);
            $archivo = $stored['filename'];
            $archivoNombre = $stored['original_name'];
        }

        $this->repository->update($id, new Documento(
            id: $id,
            titulo: $validated['titulo'],
            tipoDocumentoId: (int) $validated['tipo_documento_id'],
            fecha: $validated['fecha'],
            descripcion: $validated['descripcion'] ?? null,
            archivo: $archivo,
            archivoNombreOriginal: $archivoNombre,
        ));

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $documento = $this->get($id);

        $this->fileStorage->delete($documento->archivo);
        $this->repository->delete($id);
    }

    public function file(int $id): array
    {
        $documento = $this->get($id);

        if ($documento->archivo === null || !$this->fileStorage->exists($documento->archivo)) {
            throw new NotFoundException('El documento no tiene un archivo asociado.');
        }

        return [
            'path' => $this->fileStorage->path($documento->archivo),
            'name' => $documento->archivoNombreOriginal ?? $documento->archivo,
        ];
    }

    private function assertTipoDocumento(int $id): void
    {
        if (!$this->tipoDocumentoRepository->exists($id)) {
            throw new ValidationException(['tipo_documento_id' => ['El tipo de documento indicado no existe.']]);
        }
    }
}
