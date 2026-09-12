<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Documento;

/**
 * Da formato de salida a un Documento para el Frontend, agregando la URL de descarga
 * del archivo sin exponer detalles internos de almacenamiento.
 */
final class DocumentoPresenter
{
    /** Convierte el documento a array y agrega `archivo_url` (o null si no tiene archivo). */
    public static function toArray(Documento $documento): array
    {
        $data = $documento->toArray();
        $data['archivo_url'] = $documento->archivo !== null
            ? sprintf('/api/documentos/%d/archivo', $documento->id)
            : null;

        return $data;
    }
}
