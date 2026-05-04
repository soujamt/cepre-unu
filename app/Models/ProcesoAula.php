<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_proceso', 'id_turno', 'id_aula', 'capacidad_pau', 'estado_pau'])]
#[Table('tbl_proceso_aula', key: 'id_pau')]
class ProcesoAula extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'capacidad_pau' => 'integer',
            'estado_pau' => EstadoCatalogo::class,
        ]);
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno', 'id_tur');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_proceso_aula');
    }
}
