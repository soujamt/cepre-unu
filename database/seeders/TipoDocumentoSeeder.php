<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsJsonCatalog;
use Illuminate\Database\Seeder;

class TipoDocumentoSeeder extends Seeder
{
    use SeedsJsonCatalog;

    public function run(): void
    {
        $records = $this->withSequentialIds($this->loadRecords('tipos_documento.json'), 'id_tdo');

        $this->upsertRecords('tbl_tipo_documento', $records, ['codigo_tdo'], [
            'id_tdo',
            'nombre_tdo',
            'longitud_minima_tdo',
            'longitud_maxima_tdo',
            'estado_tdo',
        ]);
    }
}
