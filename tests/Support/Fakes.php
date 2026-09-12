<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\FileStorageInterface;
use App\Core\TransactionManagerInterface;
use App\Models\Documento;
use App\Repositories\DocumentoRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;
use Throwable;

final class InMemoryDocumentoRepository implements DocumentoRepositoryInterface
{
    /** @var array<int, Documento> */
    public array $items = [];

    public ?Throwable $failOnCreate = null;

    private int $nextId = 1;

    public function paginate(?int $tipoDocumentoId, int $limit, int $offset): array
    {
        return array_slice($this->filter($tipoDocumentoId), $offset, $limit);
    }

    public function count(?int $tipoDocumentoId = null): int
    {
        return count($this->filter($tipoDocumentoId));
    }

    public function find(int $id): ?Documento
    {
        return $this->items[$id] ?? null;
    }

    public function findByArchivoHash(string $hash, ?int $excludeId = null): ?Documento
    {
        foreach ($this->items as $id => $documento) {
            if ($documento->archivoHash === $hash && $id !== $excludeId) {
                return $documento;
            }
        }

        return null;
    }

    public function create(Documento $documento): int
    {
        if ($this->failOnCreate !== null) {
            throw $this->failOnCreate;
        }

        $id = $this->nextId++;
        $documento->id = $id;
        $this->items[$id] = $documento;

        return $id;
    }

    public function update(int $id, Documento $documento): void
    {
        $documento->id = $id;
        $this->items[$id] = $documento;
    }

    public function delete(int $id): void
    {
        unset($this->items[$id]);
    }

    /** @return Documento[] */
    private function filter(?int $tipoDocumentoId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (Documento $documento) => $tipoDocumentoId === null
                || $documento->tipoDocumentoId === $tipoDocumentoId,
        ));
    }
}

final class FakeTipoDocumentoRepository implements TipoDocumentoRepositoryInterface
{
    /** @param int[] $ids */
    public function __construct(private readonly array $ids = [1, 2, 3, 4])
    {
    }

    public function all(): array
    {
        return [];
    }

    public function exists(int $id): bool
    {
        return in_array($id, $this->ids, true);
    }
}

final class InMemoryFileStorage implements FileStorageInterface
{
    /** @var array<string, array> */
    public array $files = [];

    /** @var string[] */
    public array $deleted = [];

    private int $counter = 0;

    public function store(array $file): array
    {
        $this->counter++;
        $filename = 'stored_' . $this->counter . '.pdf';
        $hash = $file['hash'] ?? ('hash_' . $this->counter);

        $this->files[$filename] = ['original_name' => $file['name'] ?? 'archivo.pdf', 'hash' => $hash];

        return [
            'filename'      => $filename,
            'original_name' => $file['name'] ?? 'archivo.pdf',
            'hash'          => $hash,
        ];
    }

    public function delete(?string $filename): void
    {
        if ($filename === null) {
            return;
        }

        unset($this->files[$filename]);
        $this->deleted[] = $filename;
    }

    public function exists(string $filename): bool
    {
        return isset($this->files[$filename]);
    }

    public function path(string $filename): string
    {
        return '/tmp/' . $filename;
    }
}

final class FakeTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}
