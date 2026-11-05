<?php

namespace App\Support;

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use Carbon\CarbonInterface;

class ProcesoInscripcionResolver
{
    /**
     * @return array{disponible:bool,mensaje:string,proceso:?Proceso}
     */
    public function resolver(?CarbonInterface $fecha = null): array
    {
        app(EstadoAcademicoSynchronizer::class)->sync($fecha);

        $momento = $fecha ?? now();

        $proceso = Proceso::query()
            ->withCount([
                'procesosOferta as ofertas_activas_count' => fn ($query) => $query
                    ->where('estado_pof', EstadoProceso::Habilitado->value),
            ])
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->where('fecha_inicio_pro', '<=', $momento)
            ->where('fecha_fin_pro', '>=', $momento)
            ->orderBy('fecha_inicio_pro')
            ->first();

        if ($proceso === null) {
            $ultimoProcesoCerrado = Proceso::query()
                ->where('fecha_fin_pro', '<', $momento)
                ->orderByDesc('fecha_fin_pro')
                ->first();

            if ($ultimoProcesoCerrado?->fecha_fin_pro !== null) {
                return [
                    'disponible' => false,
                    'mensaje' => 'El último proceso de inscripción cerró el '.$ultimoProcesoCerrado->fecha_fin_pro->format('d/m/Y \a \l\a\s h:i a').'.',
                    'proceso' => null,
                ];
            }

            $proximoProceso = Proceso::query()
                ->where('estado_pro', EstadoProceso::Habilitado->value)
                ->where('fecha_inicio_pro', '>', $momento)
                ->orderBy('fecha_inicio_pro')
                ->first();

            if ($proximoProceso?->fecha_inicio_pro !== null) {
                return [
                    'disponible' => false,
                    'mensaje' => 'El próximo proceso de inscripción inicia el '.$proximoProceso->fecha_inicio_pro->format('d/m/Y \a \l\a\s h:i a').'.',
                    'proceso' => null,
                ];
            }

            return [
                'disponible' => false,
                'mensaje' => 'No hay un proceso de inscripcion activo en este momento.',
                'proceso' => null,
            ];
        }

        if ((int) $proceso->ofertas_activas_count < 1) {
            return [
                'disponible' => false,
                'mensaje' => 'El proceso activo no tiene ofertas habilitadas para inscripcion.',
                'proceso' => $proceso,
            ];
        }

        return [
            'disponible' => true,
            'mensaje' => 'Proceso disponible para continuar con la postulacion.',
            'proceso' => $proceso,
        ];
    }
}
