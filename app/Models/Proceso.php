<?php

namespace App\Models;

use App\Enums\EstadoProceso;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proceso de admisión. Su nombre identifica el periodo con el formato
 * AAAA-N (por ejemplo 2026-2). La inscripción se abre en la fecha y hora de
 * inicio y se cierra en la fecha y hora de fin (hora de America/Lima).
 */
#[Fillable(['codigo_pro', 'nombre_pro', 'fecha_inicio_pro', 'fecha_fin_pro', 'estado_pro'])]
#[Table('tbl_proceso', key: 'id_pro')]
class Proceso extends LegacyModel
{
    public const FORMATO_NOMBRE = '/^(\d{4})-(\d{1,2})$/';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'fecha_inicio_pro' => 'datetime',
            'fecha_fin_pro' => 'datetime',
            'estado_pro' => EstadoProceso::class,
        ]);
    }

    /**
     * Año del periodo según el nombre (2026 en "2026-2").
     */
    public function anioPeriodo(): ?int
    {
        return preg_match(self::FORMATO_NOMBRE, (string) $this->nombre_pro, $partes) ? (int) $partes[1] : null;
    }

    /**
     * Número del periodo según el nombre (2 en "2026-2").
     */
    public function numeroPeriodo(): ?int
    {
        return preg_match(self::FORMATO_NOMBRE, (string) $this->nombre_pro, $partes) ? (int) $partes[2] : null;
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

    public function procesoAulas(): HasMany
    {
        return $this->hasMany(ProcesoAula::class, 'id_proceso', 'id_pro');
    }
}
