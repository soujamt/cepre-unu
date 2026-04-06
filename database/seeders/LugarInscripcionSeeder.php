<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LugarInscripcionSeeder extends Seeder
{
    public function run(): void
    {
        $sedeIds = DB::table('tbl_sede')->pluck('id_sed', 'codigo_sed');

        foreach (CepreOfertaCatalog::lugaresInscripcion() as $record) {
            DB::table('tbl_lugar_inscripcion')->updateOrInsert(
                ['codigo_lin' => $record['codigo_lin']],
                [
                    'codigo_lin' => $record['codigo_lin'],
                    'nombre_lin' => $record['nombre_lin'],
                    'id_sede' => $sedeIds[$record['codigo_sed']],
                    'estado_lin' => $record['estado_lin'],
                ],
            );
        }
    }
}
