<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedeCarreraSeeder extends Seeder
{
    public function run(): void
    {
        $sedeIds = DB::table('tbl_sede')->pluck('id_sed', 'codigo_sed');
        $carreraIds = DB::table('tbl_carrera')->pluck('id_car', 'nombre_car');

        foreach (CepreOfertaCatalog::sedeCarreras() as $record) {
            DB::table('tbl_sede_carrera')->updateOrInsert(
                [
                    'id_sede' => $sedeIds[$record['codigo_sed']],
                    'id_carrera' => $carreraIds[$record['nombre_car']],
                ],
                [
                    'id_sede' => $sedeIds[$record['codigo_sed']],
                    'id_carrera' => $carreraIds[$record['nombre_car']],
                    'codigo_externo_sca' => $record['codigo_externo_sca'],
                    'estado_sca' => $record['estado_sca'],
                ],
            );
        }
    }
}
