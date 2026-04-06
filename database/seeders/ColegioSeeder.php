<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class ColegioSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('colegios.json');

        $this->upsertRecords('tbl_colegio', $records, ['id_col'], [
            'codigo_modular_col',
            'nombre_col',
            'id_tipo_colegio',
            'id_ubigeo',
            'direccion_col',
            'estado_col',
        ], 250);
    }
}
