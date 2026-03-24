<?php

namespace App\Models;

use App\Enums\EstadoProceso;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_semestre', 'codigo_pro', 'nombre_pro', 'fecha_inicio_pro', 'fecha_fin_pro', 'estado_pro'])]
#[Table('tbl_proceso', key: 'id_pro')]
class Proceso extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'fecha_inicio_pro' => 'date',
            'fecha_fin_pro' => 'date',
            'estado_pro' => EstadoProceso::class,
        ]);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_sem');
    }

    public function procesosOferta(): HasMany
    {
        return $this->hasMany(ProcesoOferta::class, 'id_proceso', 'id_pro');
    }

    public function conveniosProceso(): HasMany
    {
        return $this->hasMany(ConvenioProceso::class, 'id_proceso', 'id_pro');
    }

    public function reglasPago(): HasMany
    {
        return $this->hasMany(ReglaPago::class, 'id_proceso', 'id_pro');
    }

    public function requisitosExpediente(): HasMany
    {
        return $this->hasMany(RequisitoExpediente::class, 'id_proceso', 'id_pro');
    }
}
