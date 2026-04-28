<?php

namespace App\Support;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use App\Models\ProcesoAula;
use Illuminate\Database\Eloquent\Collection;

class AsignadorAulaInscripcion
{
    public function asignarDisponible(Inscripcion $inscripcion, int $idProceso): ?ProcesoAula
    {
        $aulas = $this->aulasOrdenadasPorProceso($idProceso);

        if ($aulas->isEmpty()) {
            return null;
        }

        foreach ($aulas as $procesoAula) {
            if ($this->tieneCupo($procesoAula, $inscripcion)) {
                $inscripcion->update(['id_proceso_aula' => $procesoAula->id_pau]);

                return $procesoAula;
            }
        }

        throw new \RuntimeException('No hay aulas disponibles con cupo para este proceso.');
    }

    public function validarCupo(ProcesoAula $procesoAula, Inscripcion $inscripcion): void
    {
        if (! $this->tieneCupo($procesoAula, $inscripcion)) {
            throw new \RuntimeException(
                "El aula '{$procesoAula->aula?->nombre_aul}' ya alcanzo su capacidad maxima ({$procesoAula->capacidad_pau} inscritos)."
            );
        }
    }

    /**
     * @return Collection<int, ProcesoAula>
     */
    private function aulasOrdenadasPorProceso(int $idProceso): Collection
    {
        return ProcesoAula::query()
            ->select('tbl_proceso_aula.*')
            ->join('tbl_aula', 'tbl_aula.id_aul', '=', 'tbl_proceso_aula.id_aula')
            ->with('aula')
            ->where('tbl_proceso_aula.id_proceso', $idProceso)
            ->where('tbl_proceso_aula.estado_pau', EstadoCatalogo::Habilitado->value)
            ->where('tbl_aula.estado_aul', EstadoCatalogo::Habilitado->value)
            ->orderBy('tbl_aula.lugar_aul')
            ->orderBy('tbl_aula.piso_aul')
            ->orderBy('tbl_aula.nombre_aul')
            ->lockForUpdate()
            ->get();
    }

    private function tieneCupo(ProcesoAula $procesoAula, Inscripcion $inscripcion): bool
    {
        $ocupados = $procesoAula->inscripciones()
            ->where('id_ins', '!=', $inscripcion->id_ins)
            ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)
            ->count();

        return $ocupados < $procesoAula->capacidad_pau;
    }
}
