<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidationException;
use DateTimeImmutable;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): array
    {
        $this->errors = [];
        $clean = [];

        foreach ($rules as $field => $constraints) {
            $constraints = is_array($constraints) ? $constraints : explode('|', $constraints);
            $value = $data[$field] ?? null;

            if ($value === null || (is_string($value) && trim($value) === '')) {
                if (in_array('required', $constraints, true)) {
                    $this->errors[$field][] = 'El campo es obligatorio.';
                }
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            if ($this->apply($field, $value, $constraints)) {
                $clean[$field] = $value;
            }
        }

        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }

        return $clean;
    }

    private function apply(string $field, mixed &$value, array $constraints): bool
    {
        $valid = true;

        foreach ($constraints as $constraint) {
            if ($constraint === 'required' || $constraint === 'nullable') {
                continue;
            }

            [$name, $param] = array_pad(explode(':', $constraint, 2), 2, null);

            switch ($name) {
                case 'string':
                    if (!is_string($value)) {
                        $this->errors[$field][] = 'Debe ser texto.';
                        $valid = false;
                    }
                    break;

                case 'integer':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $this->errors[$field][] = 'Debe ser un número entero.';
                        $valid = false;
                    } else {
                        $value = (int) $value;
                    }
                    break;

                case 'date':
                    if (!self::isValidDate((string) $value)) {
                        $this->errors[$field][] = 'Debe ser una fecha válida con formato YYYY-MM-DD.';
                        $valid = false;
                    }
                    break;

                case 'max':
                    if (is_string($value) && mb_strlen($value) > (int) $param) {
                        $this->errors[$field][] = sprintf('No debe superar %d caracteres.', (int) $param);
                        $valid = false;
                    }
                    break;

                case 'in':
                    if (!in_array((string) $value, explode(',', (string) $param), true)) {
                        $this->errors[$field][] = 'El valor no está dentro de las opciones permitidas.';
                        $valid = false;
                    }
                    break;
            }
        }

        return $valid;
    }

    private static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
