<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class PaisSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('paises.json'), 'id_pai');

        $this->upsertRecords('tbl_pais', $records, ['codigo_pai'], [
            'id_pai',
            'nombre_pai',
            'estado_pai',
        ]);
    }
}
