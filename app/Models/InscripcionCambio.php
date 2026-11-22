<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cambio de postulación hecho por el personal sobre una inscripción registrada.
 * `cambios_ica` guarda cada dato cambiado con su valor anterior y el nuevo.
 */
#[Fillable(['id_inscripcion', 'cambios_ica', 'motivo_ica'])]
#[Table('tbl_inscripcion_cambio', key: 'id_ica')]
class InscripcionCambio extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'cambios_ica' => 'array',
        ];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'id_ins');
    }
}
