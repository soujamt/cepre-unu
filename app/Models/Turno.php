<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_tur', 'nombre_tur', 'estado_tur'])]
#[Table('tbl_turno', key: 'id_tur')]
class Turno extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_tur' => EstadoCatalogo::class,
        ]);
    }

    public function procesosOferta(): HasMany
    {
        return $this->hasMany(ProcesoOferta::class, 'id_turno', 'id_tur');
    }
}
