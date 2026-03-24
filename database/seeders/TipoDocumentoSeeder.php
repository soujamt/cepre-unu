<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->records() as $record) {
            DB::table('tbl_tipo_documento')->updateOrInsert(
                ['codigo_tdo' => $record['codigo_tdo']],
                $record,
            );
        }
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function records(): array
    {
        return [
            [
                'codigo_tdo' => 'DNI',
                'nombre_tdo' => 'DOCUMENTO NACIONAL DE IDENTIDAD',
                'longitud_minima_tdo' => 8,
                'longitud_maxima_tdo' => 8,
                'estado_tdo' => 'HABILITADO',
            ],
            [
                'codigo_tdo' => 'CE',
                'nombre_tdo' => 'CARNET DE EXTRANJERIA',
                'longitud_minima_tdo' => 9,
                'longitud_maxima_tdo' => 12,
                'estado_tdo' => 'HABILITADO',
            ],
            [
                'codigo_tdo' => 'PAS',
                'nombre_tdo' => 'PASAPORTE',
                'longitud_minima_tdo' => 6,
                'longitud_maxima_tdo' => 12,
                'estado_tdo' => 'HABILITADO',
            ],
        ];
    }
}
