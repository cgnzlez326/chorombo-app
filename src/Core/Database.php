<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Conexión PDO perezosa y gestor de transacciones.
 * Implementa TransactionManagerInterface para que los servicios no dependan de PDO directamente.
 */
class Database implements TransactionManagerInterface
{
    private ?PDO $connection = null;

    public function __construct(private readonly array $config)
    {
    }

    /** Devuelve la conexión compartida, abriéndola la primera vez. */
    public function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->connect();
        }

        return $this->connection;
    }

    /** Ejecuta $callback dentro de una transacción y hace rollback ante cualquier excepción. */
    public function transaction(callable $callback): mixed
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $result = $callback();
            $connection->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function connect(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['name'],
            $this->config['charset'],
        );

        try {
            return new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $exception);
        }
    }
}
