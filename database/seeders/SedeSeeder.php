<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CepreOfertaCatalog::sedes() as $record) {
            DB::table('tbl_sede')->updateOrInsert(
                ['codigo_sed' => $record['codigo_sed']],
                $record,
            );
        }
    }
}
