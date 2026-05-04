<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class TipoColegioSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('tipos_colegio.json'), 'id_tco');

        $this->upsertRecords('tbl_tipo_colegio', $records, ['codigo_tco'], [
            'id_tco',
            'nombre_tco',
            'estado_tco',
        ]);
    }
}
