<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ColegioSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('colegios.json');

        Schema::withoutForeignKeyConstraints(function (): void {
            DB::table('tbl_colegio')->truncate();
        });

        foreach (array_chunk($records, 250) as $chunk) {
            DB::table('tbl_colegio')->insert($chunk);
        }
    }
}
