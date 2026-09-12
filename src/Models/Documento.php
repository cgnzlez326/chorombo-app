<?php

declare(strict_types=1);

namespace App\Models;

class Documento
{
    public function __construct(
        public ?int $id = null,
        public string $titulo = '',
        public int $tipoDocumentoId = 0,
        public string $fecha = '',
        public ?string $descripcion = null,
        public ?string $archivo = null,
        public ?string $archivoNombreOriginal = null,
        public ?string $archivoHash = null,
        public ?string $tipoDocumento = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            titulo: (string) ($row['titulo'] ?? ''),
            tipoDocumentoId: (int) ($row['tipo_documento_id'] ?? 0),
            fecha: (string) ($row['fecha'] ?? ''),
            descripcion: $row['descripcion'] ?? null,
            archivo: $row['archivo'] ?? null,
            archivoNombreOriginal: $row['archivo_nombre_original'] ?? null,
            archivoHash: $row['archivo_hash'] ?? null,
            tipoDocumento: $row['tipo_documento'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                      => $this->id,
            'titulo'                  => $this->titulo,
            'tipo_documento_id'       => $this->tipoDocumentoId,
            'tipo_documento'          => $this->tipoDocumento,
            'fecha'                   => $this->fecha,
            'descripcion'             => $this->descripcion,
            'archivo'                 => $this->archivo,
            'archivo_nombre_original' => $this->archivoNombreOriginal,
            'created_at'              => $this->createdAt,
            'updated_at'              => $this->updatedAt,
        ];
    }
}
