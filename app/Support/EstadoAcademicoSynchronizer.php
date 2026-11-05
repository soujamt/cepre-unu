<?php

namespace App\Support;

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use Carbon\CarbonInterface;

class EstadoAcademicoSynchronizer
{
    public function sync(?CarbonInterface $fecha = null): void
    {
        $fechaReferencia = $this->fechaReferencia($fecha);

        $hayProcesosVencidos = Proceso::query()
            ->where('estado_pro', '!=', EstadoProceso::Cerrado->value)
            ->whereDate('fecha_fin_pro', '<', $fechaReferencia)
            ->exists();

        if ($hayProcesosVencidos) {
            Proceso::query()
                ->where('estado_pro', '!=', EstadoProceso::Cerrado->value)
                ->whereDate('fecha_fin_pro', '<', $fechaReferencia)
                ->update([
                    'estado_pro' => EstadoProceso::Cerrado->value,
                ]);
        }
    }

    public function procesoEstaVencido(Proceso $proceso, ?CarbonInterface $fecha = null): bool
    {
        $fechaFin = $proceso->fecha_fin_pro?->toDateString();

        if ($fechaFin === null) {
            return false;
        }

        return $fechaFin < $this->fechaReferencia($fecha);
    }

    private function fechaReferencia(?CarbonInterface $fecha = null): string
    {
        return ($fecha ?? now())->toDateString();
    }
}
