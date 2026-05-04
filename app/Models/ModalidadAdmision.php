<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['codigo_mad', 'nombre_mad', 'descripcion_mad', 'estado_mad'])]
#[Table('tbl_modalidad_admision', key: 'id_mad')]
class ModalidadAdmision extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_mad' => EstadoCatalogo::class,
        ]);
    }

    public function modalidadesLugarInscripcion(): HasMany
    {
        return $this->hasMany(ModalidadLugarInscripcion::class, 'id_modalidad_admision', 'id_mad');
    }

    public function ofertasBase(): HasManyThrough
    {
        return $this->hasManyThrough(
            OfertaBase::class,
            ModalidadLugarInscripcion::class,
            'id_modalidad_admision',
            'id_modalidad_lugar_inscripcion',
            'id_mad',
            'id_mli',
        );
    }

    public function reglasPago(): HasMany
    {
        return $this->hasMany(ReglaPago::class, 'id_modalidad_admision', 'id_mad');
    }
}
