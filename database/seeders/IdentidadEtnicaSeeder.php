<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class IdentidadEtnicaSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('identidades_etnicas.json'), 'id_iet');

        $this->upsertRecords('tbl_identidad_etnica', $records, ['nombre_iet'], [
            'id_iet',
            'codigo_iet',
            'nombre_iet',
            'estado_iet',
        ]);
    }
}
