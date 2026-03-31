<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoExpedienteSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->records() as $record) {
            DB::table('tbl_tipo_expediente')->updateOrInsert(
                ['codigo_tex' => $record['codigo_tex']],
                $record,
            );
        }
    }

    /**
     * @return array<int, array<string, bool|string>>
     */
    private function records(): array
    {
        return [
            [
                'codigo_tex' => 'DNI',
                'nombre_tex' => 'DNI',
                'descripcion_tex' => 'DOCUMENTO NACIONAL DE IDENTIDAD',
                'obligatorio_tex' => true,
                'estado_tex' => 'HABILITADO',
            ],
            [
                'codigo_tex' => 'PAG',
                'nombre_tex' => 'PAGO',
                'descripcion_tex' => 'CONSTANCIA O VALIDACION DE PAGO',
                'obligatorio_tex' => true,
                'estado_tex' => 'HABILITADO',
            ],
            [
                'codigo_tex' => 'EST',
                'nombre_tex' => 'DOCUMENTO DE ESTUDIANTE',
                'descripcion_tex' => 'CERTIFICADO DE ESTUDIOS, LIBRETA DE NOTAS O CONSTANCIA',
                'obligatorio_tex' => true,
                'estado_tex' => 'HABILITADO',
            ],
        ];
    }
}
