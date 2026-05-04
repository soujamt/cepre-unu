<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class LenguaNativaSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('lenguas_nativas.json'), 'id_lna');

        $this->upsertRecords('tbl_lengua_nativa', $records, ['codigo_lna'], [
            'id_lna',
            'nombre_lna',
            'estado_lna',
        ]);
    }
}
