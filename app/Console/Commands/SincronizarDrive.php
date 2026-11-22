<?php

namespace App\Console\Commands;

use App\Models\Proceso;
use App\Support\Drive\SincronizadorDrive;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('cepre:sincronizar-drive {--proceso= : ID del proceso; por defecto el vigente}')]
#[Description('Envía las hojas DATA y PAGOS del proceso al Drive de CEPRE UNU virtual')]
class SincronizarDrive extends Command
{
    public function handle(SincronizadorDrive $sincronizador): int
    {
        $proceso = null;

        if (filled($this->option('proceso'))) {
            $proceso = Proceso::query()->find((int) $this->option('proceso'));

            if ($proceso === null) {
                $this->error('No existe el proceso indicado.');

                return self::FAILURE;
            }
        }

        try {
            $resultado = $sincronizador->sincronizar($proceso);
        } catch (RuntimeException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        foreach ($resultado['hojas'] as $hoja) {
            $this->info("{$hoja['archivo']}: {$hoja['filas']} filas.");
        }

        return self::SUCCESS;
    }
}
