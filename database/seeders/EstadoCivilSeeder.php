<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class EstadoCivilSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('estados_civiles.json'), 'id_eci');

        $this->upsertRecords('tbl_estado_civil', $records, ['codigo_eci'], [
            'id_eci',
            'nombre_eci',
            'estado_eci',
        ]);
    }
}
