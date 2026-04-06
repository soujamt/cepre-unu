<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class LenguaExtranjeraSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('lenguas_extranjeras.json'), 'id_lex');

        $this->upsertRecords('tbl_lengua_extranjera', $records, ['codigo_lex'], [
            'id_lex',
            'nombre_lex',
            'estado_lex',
        ]);
    }
}
