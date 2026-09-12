<?php

declare(strict_types=1);

namespace App\Core;

/** Contrato de validación de datos de entrada. */
interface ValidatorInterface
{
    public function validate(array $data, array $rules): array;
}
