<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModalidadAdmisionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CepreOfertaCatalog::modalidades() as $record) {
            DB::table('tbl_modalidad_admision')->updateOrInsert(
                ['codigo_mad' => $record['codigo_mad']],
                [
                    'id_mad' => (int) $record['codigo_mad'],
                    ...$record,
                ],
            );
        }
    }
}
