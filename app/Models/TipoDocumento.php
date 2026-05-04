<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_tdo', 'nombre_tdo', 'longitud_minima_tdo', 'longitud_maxima_tdo', 'estado_tdo'])]
#[Table('tbl_tipo_documento', key: 'id_tdo')]
class TipoDocumento extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_tdo' => EstadoCatalogo::class,
        ]);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_tipo_documento', 'id_tdo');
    }
}
