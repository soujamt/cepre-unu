<?php

namespace App\Models;

use App\Enums\EstadoProceso;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo_sem', 'nombre_sem', 'fecha_inicio_inscripcion_sem', 'fecha_fin_inscripcion_sem', 'estado_sem'])]
#[Table('tbl_semestre', key: 'id_sem')]
class Semestre extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'fecha_inicio_inscripcion_sem' => 'date',
            'fecha_fin_inscripcion_sem' => 'date',
            'estado_sem' => EstadoProceso::class,
        ]);
    }

    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class, 'id_semestre', 'id_sem');
    }
}
