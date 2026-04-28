<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Enums\LugarAula;
use App\Enums\PisoAula;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_aul', 'piso_aul', 'lugar_aul', 'orden_aul', 'estado_aul'])]
#[Table('tbl_aula', key: 'id_aul')]
class Aula extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'piso_aul' => PisoAula::class,
            'lugar_aul' => LugarAula::class,
            'orden_aul' => 'integer',
            'estado_aul' => EstadoCatalogo::class,
        ]);
    }

    public function procesoAulas(): HasMany
    {
        return $this->hasMany(ProcesoAula::class, 'id_aula');
    }
}
