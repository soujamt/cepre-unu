<?php

namespace App\Support;

use App\Enums\EstadoCatalogo;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\ReglaPago;
use RuntimeException;

/**
 * Comprueba que la postulación y la regla de pago elegidas en una
 * inscripcion sigan vigentes para el proceso activo. Cada metodo lanza una
 * RuntimeException con un mensaje listo para mostrar al usuario.
 */
class ValidadorVigenciaInscripcion
{
    /**
     * Comprueba que la sede, carrera y modalidad de la postulación sigan
     * habilitadas en el proceso activo (la carrera, en esa sede y modalidad).
     */
    public function postulacionVigente(int $idSede, int $idCarrera, int $idModalidad, Proceso $procesoActivo): void
    {
        $carreraHabilitada = ProcesoCarrera::query()
            ->where('id_proceso', $procesoActivo->id_pro)
            ->where('id_sede', $idSede)
            ->where('id_modalidad_admision', $idModalidad)
            ->where('id_carrera', $idCarrera)
            ->where('estado_pca', EstadoCatalogo::Habilitado->value)
            ->exists();

        if (! $carreraHabilitada) {
            throw new RuntimeException('La carrera seleccionada ya no se encuentra habilitada en esa sede y modalidad para el proceso activo.');
        }

        $modalidadHabilitada = ProcesoModalidad::query()
            ->where('id_proceso', $procesoActivo->id_pro)
            ->where('id_modalidad_admision', $idModalidad)
            ->where('estado_pmo', EstadoCatalogo::Habilitado->value)
            ->exists();

        if (! $modalidadHabilitada) {
            throw new RuntimeException('La modalidad de la postulación ya no se encuentra habilitada para el proceso activo.');
        }
    }

    public function reglaPagoVigente(int $idReglaPago, Inscripcion $inscripcion): ReglaPago
    {
        $modalidadId = $inscripcion->id_modalidad_admision;
        $procesoId = $inscripcion->id_proceso;

        if ($procesoId === null) {
            throw new RuntimeException('La inscripción ya no tiene un proceso válido para continuar con el pago.');
        }

        $reglaPago = ReglaPago::query()
            ->with([
                'conceptoPago',
                'modalidadAdmision',
            ])
            ->where('id_rpa', $idReglaPago)
            ->where('id_proceso', $procesoId)
            ->where('estado_rpa', EstadoCatalogo::Habilitado->value)
            ->whereDate('vigente_desde_rpa', '<=', now()->toDateString())
            ->whereDate('vigente_hasta_rpa', '>=', now()->toDateString())
            ->where(function ($query) use ($modalidadId): void {
                $query->whereNull('id_modalidad_admision');

                if ($modalidadId !== null) {
                    $query->orWhere('id_modalidad_admision', $modalidadId);
                }
            })
            ->first();

        if ($reglaPago === null) {
            $reglaVencida = ReglaPago::query()
                ->where('id_rpa', $idReglaPago)
                ->first();

            if ($reglaVencida?->vigente_hasta_rpa !== null) {
                throw new RuntimeException(
                    'La regla de pago seleccionada ya no se encuentra vigente para esta inscripción. Su vigencia terminó el '.$reglaVencida->vigente_hasta_rpa->format('d/m/Y').'.'
                );
            }

            throw new RuntimeException('La regla de pago seleccionada ya no se encuentra vigente para esta inscripción.');
        }

        return $reglaPago;
    }
}
