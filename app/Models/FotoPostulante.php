<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Foto del postulante, identificada por su número de documento. Se sube
 * antes de inscribir y sirve para la ficha y el carnet de cualquier proceso.
 */
#[Fillable(['numero_documento_fot', 'archivo_fot'])]
#[Table('tbl_foto_postulante', key: 'id_fot')]
class FotoPostulante extends LegacyModel
{
    public const DISCO = 'local';

    public const CARPETA = 'fotos';

    public static function deDocumento(?string $numeroDocumento): ?self
    {
        if (blank($numeroDocumento)) {
            return null;
        }

        return static::query()->where('numero_documento_fot', trim($numeroDocumento))->first();
    }

    /**
     * Imagen como data URI, para incrustarla en los PDF.
     */
    public function comoDataUri(): ?string
    {
        $disco = Storage::disk(self::DISCO);

        if (! $disco->exists($this->archivo_fot)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($disco->get($this->archivo_fot));
    }
}
