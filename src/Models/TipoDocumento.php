<?php

declare(strict_types=1);

namespace App\Models;

/** Entidad de solo lectura del catálogo de tipos de documento. */
class TipoDocumento
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {
    }

    /** Hidrata la entidad desde una fila de base de datos. */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nombre: (string) $row['nombre'],
        );
    }

    /** Representación pública de la entidad. */
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
