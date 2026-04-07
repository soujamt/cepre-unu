<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_convenio', 'id_proceso', 'fecha_inicio_cpr', 'fecha_fin_cpr', 'cupo_maximo_cpr', 'estado_cpr'])]
#[Table('tbl_convenio_proceso', key: 'id_cpr')]
class ConvenioProceso extends LegacyModel
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_inicio_cpr' => 'date',
            'fecha_fin_cpr' => 'date',
            'estado_cpr' => EstadoCatalogo::class,
        ];
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'id_convenio', 'id_con');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_pro');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_convenio_proceso', 'id_cpr');
    }

    public function reglasPago(): HasMany
    {
        return $this->hasMany(ReglaPago::class, 'id_convenio_proceso', 'id_cpr');
    }
}
