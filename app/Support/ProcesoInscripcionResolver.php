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

        $fechaReferencia = ($fecha ?? now())->toDateString();

        $proceso = Proceso::query()
            ->with('semestre')
            ->withCount([
                'procesosOferta as ofertas_activas_count' => fn ($query) => $query
                    ->where('estado_pof', EstadoProceso::Habilitado->value),
            ])
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->whereDate('fecha_inicio_pro', '<=', $fechaReferencia)
            ->whereDate('fecha_fin_pro', '>=', $fechaReferencia)
            ->whereHas('semestre', fn ($query) => $query
                ->where('estado_sem', EstadoProceso::Habilitado->value)
                ->whereDate('fecha_inicio_inscripcion_sem', '<=', $fechaReferencia)
                ->whereDate('fecha_fin_inscripcion_sem', '>=', $fechaReferencia))
            ->orderBy('fecha_inicio_pro')
            ->first();

        if ($proceso === null) {
            $ultimoProcesoCerrado = Proceso::query()
                ->with('semestre')
                ->whereDate('fecha_fin_pro', '<', $fechaReferencia)
                ->orderByDesc('fecha_fin_pro')
                ->first();

            if ($ultimoProcesoCerrado?->fecha_fin_pro !== null) {
                return [
                    'disponible' => false,
                    'mensaje' => 'El ultimo proceso de inscripcion cerro el '.$ultimoProcesoCerrado->fecha_fin_pro->format('d/m/Y').'.',
                    'proceso' => null,
                ];
            }

            $proximoProceso = Proceso::query()
                ->with('semestre')
                ->where('estado_pro', EstadoProceso::Habilitado->value)
                ->whereDate('fecha_inicio_pro', '>', $fechaReferencia)
                ->orderBy('fecha_inicio_pro')
                ->first();

            if ($proximoProceso?->fecha_inicio_pro !== null) {
                return [
                    'disponible' => false,
                    'mensaje' => 'El proximo proceso de inscripcion inicia el '.$proximoProceso->fecha_inicio_pro->format('d/m/Y').'.',
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
