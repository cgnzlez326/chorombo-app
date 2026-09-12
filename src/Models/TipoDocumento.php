<?php

declare(strict_types=1);

namespace App\Models;

class TipoDocumento
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nombre: (string) $row['nombre'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
