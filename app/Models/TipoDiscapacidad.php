<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_tdi', 'nombre_tdi', 'estado_tdi'])]
#[Table('tbl_tipo_discapacidad', key: 'id_tdi')]
class TipoDiscapacidad extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_tdi' => EstadoCatalogo::class,
        ]);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_tipo_discapacidad', 'id_tdi');
    }
}
