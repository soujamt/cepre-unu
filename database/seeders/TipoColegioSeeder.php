<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class TipoColegioSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('tipos_colegio.json');

        $this->upsertRecords('tbl_tipo_colegio', $records, ['codigo_tco'], [
            'nombre_tco',
            'estado_tco',
        ]);
    }
}
