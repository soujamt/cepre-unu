<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_sede', 'id_carrera', 'codigo_externo_sca', 'estado_sca'])]
#[Table('tbl_sede_carrera', key: 'id_sca')]
class SedeCarrera extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_sca' => EstadoCatalogo::class,
        ]);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sed');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_car');
    }

    public function ofertasBase(): HasMany
    {
        return $this->hasMany(OfertaBase::class, 'id_sede_carrera', 'id_sca');
    }
}
