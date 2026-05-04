<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_tco', 'nombre_tco', 'estado_tco'])]
#[Table('tbl_tipo_colegio', key: 'id_tco')]
class TipoColegio extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_tco' => EstadoCatalogo::class,
        ]);
    }

    public function colegios(): HasMany
    {
        return $this->hasMany(Colegio::class, 'id_tipo_colegio', 'id_tco');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_tipo_colegio', 'id_tco');
    }
}
