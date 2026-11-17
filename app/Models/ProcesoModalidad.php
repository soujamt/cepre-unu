<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modalidad habilitada para inscribirse en un proceso.
 */
#[Fillable(['id_proceso', 'id_modalidad_admision', 'estado_pmo'])]
#[Table('tbl_proceso_modalidad', key: 'id_pmo')]
class ProcesoModalidad extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_pmo' => EstadoCatalogo::class,
        ]);
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function modalidadAdmision(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdmision::class, 'id_modalidad_admision', 'id_mad');
    }
}
