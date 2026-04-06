<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_values(CepreOfertaCatalog::carreras()) as $index => $record) {
            DB::table('tbl_carrera')->updateOrInsert(
                ['codigo_car' => $record['codigo_car']],
                [
                    'id_car' => $index + 1,
                    ...$record,
                ],
            );
        }
    }
}
