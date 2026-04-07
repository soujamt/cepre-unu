<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_modalidad_admision', 'id_lugar_inscripcion', 'estado_mli'])]
#[Table('tbl_modalidad_lugar_inscripcion', key: 'id_mli')]
class ModalidadLugarInscripcion extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_mli' => EstadoCatalogo::class,
        ]);
    }

    public function modalidadAdmision(): BelongsTo
    {
        return $this->belongsTo(ModalidadAdmision::class, 'id_modalidad_admision', 'id_mad');
    }

    public function lugarInscripcion(): BelongsTo
    {
        return $this->belongsTo(LugarInscripcion::class, 'id_lugar_inscripcion', 'id_lin');
    }

    public function ofertasBase(): HasMany
    {
        return $this->hasMany(OfertaBase::class, 'id_modalidad_lugar_inscripcion', 'id_mli');
    }
}
