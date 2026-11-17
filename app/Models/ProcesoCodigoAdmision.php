<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Código que Admisión asigna en un proceso: sin carrera es el código de lugar
 * (sede + modalidad); con carrera es el código de la oferta.
 */
#[Fillable(['id_proceso', 'id_sede', 'id_modalidad_admision', 'id_carrera', 'codigo_pcd'])]
#[Table('tbl_proceso_codigo_admision', key: 'id_pcd')]
class ProcesoCodigoAdmision extends LegacyModel
{
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    /**
     * Clave de la combinación: "sede-modalidad" para el lugar y
     * "sede-modalidad-carrera" para la oferta.
     */
    public static function clave(int $idSede, int $idModalidad, ?int $idCarrera = null): string
    {
        return implode('-', array_filter([$idSede, $idModalidad, $idCarrera], fn (?int $id): bool => $id !== null));
    }
}
