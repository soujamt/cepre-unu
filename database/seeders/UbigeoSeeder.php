<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class UbigeoSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('ubigeos.json');

        $this->upsertRecords('tbl_ubigeo', $records, ['id_ubi'], [
            'codigo_departamento_ubi',
            'codigo_provincia_ubi',
            'codigo_distrito_ubi',
            'nombre_departamento_ubi',
            'nombre_provincia_ubi',
            'nombre_distrito_ubi',
            'estado_ubi',
        ]);
    }
}
