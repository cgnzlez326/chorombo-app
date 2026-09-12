<?php

declare(strict_types=1);

namespace App\Core;

/** Contrato para ejecutar operaciones dentro de una transacción. */
interface TransactionManagerInterface
{
    public function transaction(callable $callback): mixed;
}
