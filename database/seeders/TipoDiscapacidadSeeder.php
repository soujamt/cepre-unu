<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class TipoDiscapacidadSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('tipos_discapacidad.json');

        $this->upsertRecords('tbl_tipo_discapacidad', $records, ['codigo_tdi'], [
            'nombre_tdi',
            'estado_tdi',
        ]);
    }
}
