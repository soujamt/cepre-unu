<?php

namespace App\Models;

use App\Enums\EstadoProceso;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_proceso', 'id_oferta_base', 'id_turno', 'estado_pof'])]
#[Table('tbl_proceso_oferta', key: 'id_pof')]
class ProcesoOferta extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_pof' => EstadoProceso::class,
        ]);
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function ofertaBase(): BelongsTo
    {
        return $this->belongsTo(OfertaBase::class, 'id_oferta_base', 'id_ofb');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno', 'id_tur');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_proceso_oferta', 'id_pof');
    }
}
