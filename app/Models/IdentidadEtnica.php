<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_iet', 'nombre_iet', 'estado_iet'])]
#[Table('tbl_identidad_etnica', key: 'id_iet')]
class IdentidadEtnica extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_iet' => EstadoCatalogo::class,
        ]);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_identidad_etnica', 'id_iet');
    }
}
