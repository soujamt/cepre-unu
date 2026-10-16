<?php

namespace App\Support;

use App\Models\Inscripcion;
use App\Models\ProcesoOferta;

/**
 * Genera el codigo visible de una inscripcion con el formato
 * {AA}{S}{PRO}{NNNN}: los dos ultimos digitos del anio y el numero del
 * semestre (de codigo_sem, por ejemplo "2026_1"), las tres primeras letras
 * del codigo del proceso en mayusculas y el correlativo de la inscripcion
 * dentro del proceso con cuatro digitos. Ejemplo: 261ORD0001.
 */
class GeneradorCodigoInscripcion
{
    public function generar(Inscripcion $inscripcion): string
    {
        $procesoOferta = ProcesoOferta::query()
            ->with(['proceso.semestre'])
            ->find($inscripcion->id_proceso_oferta);

        $proceso = $procesoOferta?->proceso;
        $semestre = $proceso?->semestre;

        [$anio, $numeroSemestre] = array_pad(
            explode('_', (string) ($semestre?->codigo_sem ?? '0_0')),
            2,
            '0',
        );

        $anio = substr($anio, -2);

        $codigoProceso = mb_strtoupper(
            mb_substr((string) ($proceso?->codigo_pro ?? 'PRO'), 0, 3, 'UTF-8'),
            'UTF-8',
        );

        $correlativo = Inscripcion::query()
            ->whereHas(
                'procesoOferta',
                fn ($query) => $query->where('id_proceso', $proceso?->id_pro),
            )
            ->where('id_ins', '<=', $inscripcion->id_ins)
            ->count();

        return "{$anio}{$numeroSemestre}{$codigoProceso}".str_pad((string) $correlativo, 4, '0', STR_PAD_LEFT);
    }
}
