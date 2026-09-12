<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\FileStorage;
use App\Core\Validator;
use App\Exceptions\DuplicateException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Documento;
use App\Repositories\DocumentoRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;
use Throwable;

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
        private readonly Database $database,
    ) {
    }

    /** @return array{items: Documento[], total: int, page: int, per_page: int} */
    public function list(?int $tipoDocumentoId, int $page, int $perPage): array
    {
        return [
            'items'    => $this->repository->paginate($tipoDocumentoId, $perPage, ($page - 1) * $perPage),
            'total'    => $this->repository->count($tipoDocumentoId),
            'page'     => $page,
            'per_page' => $perPage,
        ];
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

        $stored = null;

        if ($file !== null) {
            $stored = $this->fileStorage->store($file);
            $this->assertArchivoNoDuplicado($stored);
        }

        try {
            $id = $this->database->transaction(fn (): int => $this->repository->create(new Documento(
                titulo: $validated['titulo'],
                tipoDocumentoId: (int) $validated['tipo_documento_id'],
                fecha: $validated['fecha'],
                descripcion: $validated['descripcion'] ?? null,
                archivo: $stored['filename'] ?? null,
                archivoNombreOriginal: $stored['original_name'] ?? null,
                archivoHash: $stored['hash'] ?? null,
            )));
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->safeDelete($stored['filename']);
            }

            throw $exception;
        }

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
        $archivoHash = $current->archivoHash;
        $stored = null;

        if ($file !== null) {
            $stored = $this->fileStorage->store($file);
            $this->assertArchivoNoDuplicado($stored, $id);
            $archivo = $stored['filename'];
            $archivoNombre = $stored['original_name'];
            $archivoHash = $stored['hash'];
        }

        try {
            $this->database->transaction(function () use ($id, $validated, $archivo, $archivoNombre, $archivoHash): void {
                $this->repository->update($id, new Documento(
                    id: $id,
                    titulo: $validated['titulo'],
                    tipoDocumentoId: (int) $validated['tipo_documento_id'],
                    fecha: $validated['fecha'],
                    descripcion: $validated['descripcion'] ?? null,
                    archivo: $archivo,
                    archivoNombreOriginal: $archivoNombre,
                    archivoHash: $archivoHash,
                ));
            });
        } catch (Throwable $exception) {
            if ($stored !== null) {
                $this->safeDelete($stored['filename']);
            }

            throw $exception;
        }

        if ($stored !== null) {
            $this->safeDelete($current->archivo);
        }

        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $documento = $this->get($id);

        $this->database->transaction(function () use ($id): void {
            $this->repository->delete($id);
        });

        $this->safeDelete($documento->archivo);
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

    private function assertArchivoNoDuplicado(array $stored, ?int $excludeId = null): void
    {
        $existente = $this->repository->findByArchivoHash($stored['hash'], $excludeId);

        if ($existente !== null) {
            $this->safeDelete($stored['filename']);

            throw new DuplicateException(details: [
                'documento_id' => $existente->id,
                'titulo'       => $existente->titulo,
            ]);
        }
    }

    private function safeDelete(?string $filename): void
    {
        try {
            $this->fileStorage->delete($filename);
        } catch (Throwable $exception) {
            error_log(sprintf(
                '[chorombo-api] No se pudo eliminar el archivo %s: %s',
                (string) $filename,
                $exception->getMessage(),
            ));
        }
    }
}
