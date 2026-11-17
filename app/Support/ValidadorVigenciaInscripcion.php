<?php

namespace App\Support;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use RuntimeException;

/**
 * Comprueba que la oferta y la regla de pago elegidas en una
 * inscripcion sigan vigentes para el proceso activo. Cada metodo lanza una
 * RuntimeException con un mensaje listo para mostrar al usuario.
 */
class ValidadorVigenciaInscripcion
{
    public function ofertaVigente(int $idProcesoOferta, Proceso $procesoActivo): ProcesoOferta
    {
        $procesoOferta = ProcesoOferta::query()
            ->with(['ofertaBase.modalidadLugarInscripcion'])
            ->where('id_pof', $idProcesoOferta)
            ->where('id_proceso', $procesoActivo->id_pro)
            ->where('estado_pof', EstadoProceso::Habilitado->value)
            ->first();

        if ($procesoOferta === null) {
            throw new RuntimeException('La oferta seleccionada ya no se encuentra disponible para el proceso activo.');
        }

        return $procesoOferta;
    }

    public function reglaPagoVigente(int $idReglaPago, Inscripcion $inscripcion): ReglaPago
    {
        $modalidadId = $inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision;
        $procesoId = $inscripcion->procesoOferta?->id_proceso;

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
