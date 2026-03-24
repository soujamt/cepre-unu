<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_car', 'nombre_car', 'estado_car'])]
#[Table('tbl_carrera', key: 'id_car')]
class Carrera extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_car' => EstadoCatalogo::class,
        ]);
    }

    public function sedeCarreras(): HasMany
    {
        return $this->hasMany(SedeCarrera::class, 'id_carrera', 'id_car');
    }
}
