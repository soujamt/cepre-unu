<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class LenguaExtranjeraSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('lenguas_extranjeras.json');

        $this->upsertRecords('tbl_lengua_extranjera', $records, ['codigo_lex'], [
            'nombre_lex',
            'estado_lex',
        ]);
    }
}
