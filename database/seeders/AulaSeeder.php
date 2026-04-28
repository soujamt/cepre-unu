<?php

namespace Database\Seeders;

use App\Enums\LugarAula;
use App\Enums\PisoAula;
use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pisos = [
            PisoAula::Piso1,
            PisoAula::Piso2,
            PisoAula::Piso3,
            PisoAula::Piso4,
            PisoAula::Piso5,
        ];

        $lugares = [
            LugarAula::Unu,
            LugarAula::Uap,
        ];

        for ($i = 1; $i <= 20; $i++) {
            $numero = str_pad($i, 3, '0', STR_PAD_LEFT);
            $piso = $pisos[array_rand($pisos)];
            $lugar = $lugares[array_rand($lugares)];

            Aula::create([
                'nombre_aul' => "Aula {$numero}",
                'piso_aul' => $piso->value,
                'lugar_aul' => $lugar->value,
                'orden_aul' => $i,
            ]);
        }
    }
}
