<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['codigo_tur', 'nombre_tur', 'hora_inicio_tur', 'hora_fin_tur', 'estado_tur'])]
#[Table('tbl_turno', key: 'id_tur')]
class Turno extends LegacyModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estado_tur' => EstadoCatalogo::class,
        ]);
    }

    /**
     * Nombre con horario, como en el sistema anterior: "MAÑANA (07:00 a. m. - 12:00 p. m.)".
     */
    public function descripcionConHorario(): string
    {
        if ($this->hora_inicio_tur === null || $this->hora_fin_tur === null) {
            return (string) $this->nombre_tur;
        }

        $formato = fn (string $hora): string => Carbon::createFromFormat('H:i:s', strlen($hora) === 5 ? "{$hora}:00" : $hora)->format('h:i a');

        return "{$this->nombre_tur} ({$formato($this->hora_inicio_tur)} - {$formato($this->hora_fin_tur)})";
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_turno', 'id_tur');
    }

    public function procesosOferta(): HasMany
    {
        return $this->hasMany(ProcesoOferta::class, 'id_turno', 'id_tur');
    }

    public function procesoAulas(): HasMany
    {
        return $this->hasMany(ProcesoAula::class, 'id_turno', 'id_tur');
    }
}
