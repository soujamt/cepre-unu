<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_modalidad_admision', 'id_lugar_inscripcion', 'id_sede_carrera', 'estado_ofb'])]
#[Table('tbl_oferta_base', key: 'id_ofb')]
class OfertaBase extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_ofb' => EstadoCatalogo::class,
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

    public function sedeCarrera(): BelongsTo
    {
        return $this->belongsTo(SedeCarrera::class, 'id_sede_carrera', 'id_sca');
    }

    public function procesosOferta(): HasMany
    {
        return $this->hasMany(ProcesoOferta::class, 'id_oferta_base', 'id_ofb');
    }
}
