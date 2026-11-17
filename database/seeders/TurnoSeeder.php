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
                'hora_inicio_tur' => '07:00',
                'hora_fin_tur' => '12:00',
                'nombre_tur' => 'MAÑANA',
                'estado_tur' => 'HABILITADO',
            ],
            [
                'id_tur' => 2,
                'codigo_tur' => 'TAR',
                'hora_inicio_tur' => '13:00',
                'hora_fin_tur' => '17:00',
                'nombre_tur' => 'TARDE',
                'estado_tur' => 'HABILITADO',
            ],
            [
                'id_tur' => 3,
                'codigo_tur' => 'NOC',
                'hora_inicio_tur' => '17:00',
                'hora_fin_tur' => '22:00',
                'nombre_tur' => 'NOCHE',
                'estado_tur' => 'HABILITADO',
            ],
        ];
    }
}
