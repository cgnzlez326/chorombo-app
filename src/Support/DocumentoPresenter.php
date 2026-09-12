<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Documento;

final class DocumentoPresenter
{
    public static function toArray(Documento $documento): array
    {
        $data = $documento->toArray();
        $data['archivo_url'] = $documento->archivo !== null
            ? sprintf('/api/documentos/%d/archivo', $documento->id)
            : null;

        return $data;
    }
}
