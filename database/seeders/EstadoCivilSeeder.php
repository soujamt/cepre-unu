<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class EstadoCivilSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->loadRecords('estados_civiles.json');

        $this->upsertRecords('tbl_estado_civil', $records, ['codigo_eci'], [
            'nombre_eci',
            'estado_eci',
        ]);
    }
}
