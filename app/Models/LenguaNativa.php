<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_lna', 'nombre_lna', 'estado_lna'])]
#[Table('tbl_lengua_nativa', key: 'id_lna')]
class LenguaNativa extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_lna' => EstadoCatalogo::class,
        ]);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_lengua_nativa', 'id_lna');
    }
}
