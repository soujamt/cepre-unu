<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoColegioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->records() as $record) {
            DB::table('tbl_tipo_colegio')->updateOrInsert(
                ['codigo_tco' => $record['codigo_tco']],
                $record,
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function records(): array
    {
        return [
            [
                'codigo_tco' => 'PUB',
                'nombre_tco' => 'PUBLICO',
                'estado_tco' => 'HABILITADO',
            ],
            [
                'codigo_tco' => 'PRI',
                'nombre_tco' => 'PRIVADO',
                'estado_tco' => 'HABILITADO',
            ],
        ];
    }
}
