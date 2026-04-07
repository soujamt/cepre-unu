<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_externo_ofb', 'id_modalidad_lugar_inscripcion', 'id_sede_carrera', 'estado_ofb'])]
#[Table('tbl_oferta_base', key: 'id_ofb')]
class OfertaBase extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_ofb' => EstadoCatalogo::class,
        ]);
    }

    public function modalidadLugarInscripcion(): BelongsTo
    {
        return $this->belongsTo(ModalidadLugarInscripcion::class, 'id_modalidad_lugar_inscripcion', 'id_mli');
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
