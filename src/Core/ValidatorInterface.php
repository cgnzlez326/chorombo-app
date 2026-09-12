<?php

declare(strict_types=1);

namespace App\Core;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): array;
}
