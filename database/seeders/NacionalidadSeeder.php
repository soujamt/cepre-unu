<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class NacionalidadSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('nacionalidades.json');

        $this->upsertRecords('tbl_nacionalidad', $records, ['codigo_nac'], [
            'nombre_nac',
            'estado_nac',
        ]);
    }
}
