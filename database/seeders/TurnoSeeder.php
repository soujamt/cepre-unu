<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TurnoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->records() as $record) {
            DB::table('tbl_turno')->updateOrInsert(
                ['codigo_tur' => $record['codigo_tur']],
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
                'id_tur' => 1,
                'codigo_tur' => 'MAN',
                'nombre_tur' => 'MANANA',
                'estado_tur' => 'HABILITADO',
            ],
            [
                'id_tur' => 2,
                'codigo_tur' => 'TAR',
                'nombre_tur' => 'TARDE',
                'estado_tur' => 'HABILITADO',
            ],
            [
                'id_tur' => 3,
                'codigo_tur' => 'NOC',
                'nombre_tur' => 'NOCHE',
                'estado_tur' => 'HABILITADO',
            ],
        ];
    }
}
