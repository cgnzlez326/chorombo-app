<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\TipoDocumento;
use PDO;

/** Acceso a datos del catálogo de tipos de documento. */
class TipoDocumentoRepository implements TipoDocumentoRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    /** Lista todos los tipos de documento ordenados por nombre. */
    public function all(): array
    {
        $statement = $this->connection()->query('SELECT id, nombre FROM tipos_documento ORDER BY nombre ASC');

        return array_map([TipoDocumento::class, 'fromRow'], $statement->fetchAll());
    }

    /** Indica si existe un tipo de documento con ese id. */
    public function exists(int $id): bool
    {
        $statement = $this->connection()->prepare('SELECT 1 FROM tipos_documento WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    /** Conexión PDO compartida. */
    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
