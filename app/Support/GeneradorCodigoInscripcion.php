<?php

namespace App\Support;

use App\Models\Inscripcion;
use App\Models\ProcesoOferta;

/**
 * Genera el código visible de una inscripción con el formato {AA}{N}{NNNN}:
 * los dos últimos dígitos del año y el número del periodo, tomados del nombre
 * del proceso (por ejemplo "2026-2"), y el correlativo de la inscripción dentro
 * del proceso con cuatro dígitos. Ejemplo: 2620001.
 */
class GeneradorCodigoInscripcion
{
    public function generar(Inscripcion $inscripcion): string
    {
        $proceso = ProcesoOferta::query()
            ->with('proceso')
            ->find($inscripcion->id_proceso_oferta)
            ?->proceso;

        $anio = $proceso?->anioPeriodo() ?? (int) now()->format('Y');
        $numeroPeriodo = $proceso?->numeroPeriodo() ?? 0;

        // Incluye las inscripciones eliminadas para no reutilizar un correlativo
        $correlativo = Inscripcion::withTrashed()
            ->whereHas(
                'procesoOferta',
                fn ($query) => $query->where('id_proceso', $proceso?->id_pro),
            )
            ->where('id_ins', '<=', $inscripcion->id_ins)
            ->count();

        return substr((string) $anio, -2).$numeroPeriodo.str_pad((string) $correlativo, 4, '0', STR_PAD_LEFT);
    }
}
