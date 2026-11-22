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
#[Fillable(['codigo_pro', 'nombre_pro', 'fecha_inicio_pro', 'fecha_fin_pro', 'anio_egreso_max_ordinario_pro', 'estado_pro'])]
#[Table('tbl_proceso', key: 'id_pro')]
class Proceso extends LegacyModel
{
    public const FORMATO_NOMBRE = '/^(\d{4})-(\d{1,2})$/';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'fecha_inicio_pro' => 'datetime',
            'fecha_fin_pro' => 'datetime',
            'anio_egreso_max_ordinario_pro' => 'integer',
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

    /**
     * Último año de egreso que entra como ORDINARIO. Si no se configuró, es el
     * año anterior al de apertura: quien termina el colegio ese año aún no egresó.
     */
    public function anioEgresoMaximoOrdinario(): int
    {
        return $this->anio_egreso_max_ordinario_pro
            ?? ((int) ($this->fecha_inicio_pro?->format('Y') ?? now()->format('Y'))) - 1;
    }

    /**
     * Proceso vigente según sus fechas o, si no hay, el más reciente con inscripciones.
     */
    public static function actual(): ?self
    {
        return static::query()
            ->where('fecha_inicio_pro', '<=', now())
            ->where('fecha_fin_pro', '>=', now())
            ->orderByDesc('fecha_inicio_pro')
            ->orderByDesc('id_pro')
            ->first()
            ?? static::query()->whereHas('inscripciones')->orderByDesc('fecha_inicio_pro')->orderByDesc('id_pro')->first();
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_proceso', 'id_pro');
    }

    public function procesoModalidades(): HasMany
    {
        return $this->hasMany(ProcesoModalidad::class, 'id_proceso', 'id_pro');
    }

    public function procesoCarreras(): HasMany
    {
        return $this->hasMany(ProcesoCarrera::class, 'id_proceso', 'id_pro');
    }

    public function procesosOferta(): HasMany
    {
        return $this->hasMany(ProcesoOferta::class, 'id_proceso', 'id_pro');
    }

    public function reglasPago(): HasMany
    {
        return $this->hasMany(ReglaPago::class, 'id_proceso', 'id_pro');
    }

    public function procesoAulas(): HasMany
    {
        return $this->hasMany(ProcesoAula::class, 'id_proceso', 'id_pro');
    }
}
