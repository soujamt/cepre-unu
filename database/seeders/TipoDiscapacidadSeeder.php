<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class TipoDiscapacidadSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('tipos_discapacidad.json'), 'id_tdi');

        $this->upsertRecords('tbl_tipo_discapacidad', $records, ['codigo_tdi'], [
            'id_tdi',
            'nombre_tdi',
            'estado_tdi',
        ]);
    }
}
