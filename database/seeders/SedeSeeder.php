<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_values(CepreOfertaCatalog::sedes()) as $index => $record) {
            DB::table('tbl_sede')->updateOrInsert(
                ['codigo_sed' => $record['codigo_sed']],
                [
                    'id_sed' => $index + 1,
                    ...$record,
                ],
            );
        }
    }
}
