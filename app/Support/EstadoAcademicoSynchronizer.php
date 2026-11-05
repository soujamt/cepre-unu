<?php

namespace App\Support;

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use Carbon\CarbonInterface;

/**
 * Cierra automáticamente los procesos cuya fecha y hora de fin ya pasaron.
 */
class EstadoAcademicoSynchronizer
{
    public function sync(?CarbonInterface $fecha = null): void
    {
        $momento = $this->momentoReferencia($fecha);

        $hayProcesosVencidos = Proceso::query()
            ->where('estado_pro', '!=', EstadoProceso::Cerrado->value)
            ->where('fecha_fin_pro', '<', $momento)
            ->exists();

        if ($hayProcesosVencidos) {
            Proceso::query()
                ->where('estado_pro', '!=', EstadoProceso::Cerrado->value)
                ->where('fecha_fin_pro', '<', $momento)
                ->update([
                    'estado_pro' => EstadoProceso::Cerrado->value,
                ]);
        }
    }

    public function procesoEstaVencido(Proceso $proceso, ?CarbonInterface $fecha = null): bool
    {
        if ($proceso->fecha_fin_pro === null) {
            return false;
        }

        return $proceso->fecha_fin_pro->lt($this->momentoReferencia($fecha));
    }

    private function momentoReferencia(?CarbonInterface $fecha = null): CarbonInterface
    {
        return $fecha ?? now();
    }
}
