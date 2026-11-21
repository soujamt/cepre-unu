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
     * Dirección de la foto (ruta fotos.ver), con la fecha de cambio para no usar una copia vieja.
     */
    public function url(): string
    {
        return route('fotos.ver', $this->numero_documento_fot).'?v='.$this->actualizado_en?->timestamp;
    }

    /**
     * Direcciones de las fotos de varios documentos en una sola consulta.
     *
     * @param  iterable<int, string|null>  $documentos
     * @return array<string, string> Documento => dirección; los que no tienen foto no aparecen.
     */
    public static function urlsDeDocumentos(iterable $documentos): array
    {
        $documentos = collect($documentos)->filter()->map(fn (string $documento): string => trim($documento))->unique()->values();

        if ($documentos->isEmpty()) {
            return [];
        }

        return static::query()
            ->whereIn('numero_documento_fot', $documentos->all())
            ->get()
            ->mapWithKeys(fn (self $foto): array => [$foto->numero_documento_fot => $foto->url()])
            ->all();
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
