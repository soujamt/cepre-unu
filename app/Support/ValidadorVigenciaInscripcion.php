<?php

namespace App\Support;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\ConvenioProceso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use RuntimeException;

/**
 * Comprueba que la oferta, el convenio y la regla de pago elegidos en una
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

    public function convenioVigente(?int $idConvenioProceso, Proceso $procesoActivo, bool $lock = false): ?ConvenioProceso
    {
        if ($idConvenioProceso === null) {
            return null;
        }

        $query = ConvenioProceso::query()
            ->with('convenio')
            ->where('id_cpr', $idConvenioProceso)
            ->where('id_proceso', $procesoActivo->id_pro)
            ->where('estado_cpr', EstadoCatalogo::Habilitado->value)
            ->whereDate('fecha_inicio_cpr', '<=', now()->toDateString())
            ->whereDate('fecha_fin_cpr', '>=', now()->toDateString());

        if ($lock) {
            $query->lockForUpdate();
        }

        $convenioProceso = $query->first();

        if ($convenioProceso === null) {
            $convenioVencido = ConvenioProceso::query()
                ->with('convenio')
                ->where('id_cpr', $idConvenioProceso)
                ->first();

            if ($convenioVencido?->fecha_fin_cpr !== null) {
                throw new RuntimeException(
                    'El convenio asociado a la inscripción ya no se encuentra vigente para el proceso activo. Su vigencia terminó el '.$convenioVencido->fecha_fin_cpr->format('d/m/Y').'.'
                );
            }

            throw new RuntimeException('El convenio asociado a la inscripción ya no se encuentra vigente para el proceso activo.');
        }

        return $convenioProceso;
    }

    public function reglaPagoVigente(int $idReglaPago, Inscripcion $inscripcion): ReglaPago
    {
        $modalidadId = $inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->id_modalidad_admision;
        $convenioProcesoId = $inscripcion->id_convenio_proceso;
        $procesoId = $inscripcion->procesoOferta?->id_proceso;

        if ($procesoId === null) {
            throw new RuntimeException('La inscripción ya no tiene un proceso válido para continuar con el pago.');
        }

        $reglaPago = ReglaPago::query()
            ->with([
                'conceptoPago',
                'modalidadAdmision',
                'convenioProceso.convenio',
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
            ->where(function ($query) use ($convenioProcesoId): void {
                $query->whereNull('id_convenio_proceso');

                if ($convenioProcesoId !== null) {
                    $query->orWhere('id_convenio_proceso', $convenioProcesoId);
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
