<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_lex', 'nombre_lex', 'estado_lex'])]
#[Table('tbl_lengua_extranjera', key: 'id_lex')]
class LenguaExtranjera extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_lex' => EstadoCatalogo::class,
        ]);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_lengua_extranjera', 'id_lex');
    }
}
