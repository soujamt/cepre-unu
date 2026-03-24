<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_pai', 'nombre_pai', 'estado_pai'])]
#[Table('tbl_pais', key: 'id_pai')]
class Pais extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_pai' => EstadoCatalogo::class,
        ]);
    }

    public function personasNacimiento(): HasMany
    {
        return $this->hasMany(Persona::class, 'id_pais_nacimiento', 'id_pai');
    }

    public function inscripcionesColegio(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pais_colegio', 'id_pai');
    }
}
