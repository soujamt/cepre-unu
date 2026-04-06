<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class NacionalidadSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('nacionalidades.json'), 'id_nac');

        $this->upsertRecords('tbl_nacionalidad', $records, ['codigo_nac'], [
            'id_nac',
            'nombre_nac',
            'estado_nac',
        ]);
    }
}
