<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Exceptions\DuplicateException;
use App\Models\Documento;
use PDO;
use PDOException;
use PDOStatement;

class DocumentoRepository implements DocumentoRepositoryInterface
{
    private const SELECT = <<<SQL
        SELECT d.id, d.titulo, d.tipo_documento_id, d.fecha, d.descripcion,
               d.archivo, d.archivo_nombre_original, d.archivo_hash,
               d.created_at, d.updated_at,
               t.nombre AS tipo_documento
        FROM documentos d
        INNER JOIN tipos_documento t ON t.id = d.tipo_documento_id
        SQL;

    public function __construct(private readonly Database $database)
    {
    }

    public function all(?int $tipoDocumentoId = null): array
    {
        $sql = self::SELECT;
        $params = [];

        if ($tipoDocumentoId !== null) {
            $sql .= ' WHERE d.tipo_documento_id = :tipo_documento_id';
            $params[':tipo_documento_id'] = $tipoDocumentoId;
        }

        $sql .= ' ORDER BY d.fecha DESC, d.id DESC';

        $statement = $this->connection()->prepare($sql);

        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, PDO::PARAM_INT);
        }

        $statement->execute();

        return array_map([Documento::class, 'fromRow'], $statement->fetchAll());
    }

    public function find(int $id): ?Documento
    {
        $statement = $this->connection()->prepare(self::SELECT . ' WHERE d.id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : Documento::fromRow($row);
    }

    public function findByArchivoHash(string $hash, ?int $excludeId = null): ?Documento
    {
        $sql = self::SELECT . ' WHERE d.archivo_hash = :hash';

        if ($excludeId !== null) {
            $sql .= ' AND d.id <> :exclude_id';
        }

        $statement = $this->connection()->prepare($sql);
        $statement->bindValue(':hash', $hash);

        if ($excludeId !== null) {
            $statement->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }

        $statement->execute();
        $row = $statement->fetch();

        return $row === false ? null : Documento::fromRow($row);
    }

    public function create(Documento $documento): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO documentos (titulo, tipo_documento_id, fecha, descripcion, archivo, archivo_nombre_original, archivo_hash)
             VALUES (:titulo, :tipo_documento_id, :fecha, :descripcion, :archivo, :archivo_nombre_original, :archivo_hash)'
        );

        $this->bindDocumento($statement, $documento);
        $this->execute($statement);

        return (int) $this->connection()->lastInsertId();
    }

    public function update(int $id, Documento $documento): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE documentos
             SET titulo = :titulo,
                 tipo_documento_id = :tipo_documento_id,
                 fecha = :fecha,
                 descripcion = :descripcion,
                 archivo = :archivo,
                 archivo_nombre_original = :archivo_nombre_original,
                 archivo_hash = :archivo_hash
             WHERE id = :id'
        );

        $this->bindDocumento($statement, $documento);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $this->execute($statement);
    }

    public function delete(int $id): void
    {
        $statement = $this->connection()->prepare('DELETE FROM documentos WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
    }

    private function bindDocumento(PDOStatement $statement, Documento $documento): void
    {
        $statement->bindValue(':titulo', $documento->titulo);
        $statement->bindValue(':tipo_documento_id', $documento->tipoDocumentoId, PDO::PARAM_INT);
        $statement->bindValue(':fecha', $documento->fecha);
        $statement->bindValue(':descripcion', $documento->descripcion, $this->paramType($documento->descripcion));
        $statement->bindValue(':archivo', $documento->archivo, $this->paramType($documento->archivo));
        $statement->bindValue(
            ':archivo_nombre_original',
            $documento->archivoNombreOriginal,
            $this->paramType($documento->archivoNombreOriginal),
        );
        $statement->bindValue(':archivo_hash', $documento->archivoHash, $this->paramType($documento->archivoHash));
    }

    private function execute(PDOStatement $statement): void
    {
        try {
            $statement->execute();
        } catch (PDOException $exception) {
            if ($this->isDuplicateKey($exception)) {
                throw new DuplicateException();
            }

            throw $exception;
        }
    }

    private function isDuplicateKey(PDOException $exception): bool
    {
        return ($exception->errorInfo[0] ?? $exception->getCode()) === '23000'
            && (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    private function paramType(?string $value): int
    {
        return $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
