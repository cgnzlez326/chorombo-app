<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\TipoDocumento;
use PDO;

class TipoDocumentoRepository implements TipoDocumentoRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function all(): array
    {
        $statement = $this->connection()->query('SELECT id, nombre FROM tipos_documento ORDER BY nombre ASC');

        return array_map([TipoDocumento::class, 'fromRow'], $statement->fetchAll());
    }

    public function exists(int $id): bool
    {
        $statement = $this->connection()->prepare('SELECT 1 FROM tipos_documento WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}
