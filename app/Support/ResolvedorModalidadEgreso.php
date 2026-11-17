<?php

namespace App\Support;

use App\Enums\CondicionEgresoModalidad;
use App\Enums\EstadoCatalogo;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;

/**
 * Asigna la modalidad según el año de egreso del colegio: quien ya terminó
 * entra como egresado (ORDINARIO) y quien aún cursa 4.° o 5.° como estudiante
 * en colegio (EXTRAORDINARIO O RESERVA).
 */
class ResolvedorModalidadEgreso
{
    public function condicion(Proceso $proceso, int $anioEgreso): CondicionEgresoModalidad
    {
        return $anioEgreso <= $proceso->anioEgresoMaximoOrdinario()
            ? CondicionEgresoModalidad::Egresado
            : CondicionEgresoModalidad::EnColegio;
    }

    /**
     * Modalidad habilitada en el proceso que corresponde al año de egreso, o
     * null si el proceso no tiene habilitada ninguna para esa condición.
     */
    public function resolver(Proceso $proceso, int $anioEgreso): ?ModalidadAdmision
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->where('condicion_egreso_mad', $this->condicion($proceso, $anioEgreso)->value)
            ->whereHas('procesoModalidades', fn ($query) => $query
                ->where('id_proceso', $proceso->id_pro)
                ->where('estado_pmo', EstadoCatalogo::Habilitado->value))
            ->orderBy('id_mad')
            ->first();
    }
}
