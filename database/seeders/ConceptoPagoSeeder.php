<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConceptoPagoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->records() as $record) {
            DB::table('tbl_concepto_pago')->updateOrInsert(
                ['codigo_cpg' => $record['codigo_cpg']],
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
                'id_cpg' => 1,
                'codigo_cpg' => 'INS_UNA',
                'nombre_cpg' => 'INSCRIPCION UNA CUOTA',
                'descripcion_cpg' => 'INSCRIPCION PAGADA EN UNA SOLA CUOTA',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'id_cpg' => 2,
                'codigo_cpg' => 'INS_CUO',
                'nombre_cpg' => 'INSCRIPCION MAS CUOTAS',
                'descripcion_cpg' => 'INSCRIPCION PAGADA EN VARIAS CUOTAS',
                'permite_cuotas_cpg' => true,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'id_cpg' => 3,
                'codigo_cpg' => 'DUP_CAR',
                'nombre_cpg' => 'DUPLICADO CARNET / CAMBIO CARRERA',
                'descripcion_cpg' => 'DUPLICADO DE CARNET O CAMBIO DE CARRERA',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'id_cpg' => 4,
                'codigo_cpg' => 'GUIA',
                'nombre_cpg' => 'GUIA',
                'descripcion_cpg' => 'PAGO POR GUIA',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'id_cpg' => 5,
                'codigo_cpg' => 'HIJ_ADM',
                'nombre_cpg' => 'DESCUENTO HIJO ADMINISTRATIVO',
                'descripcion_cpg' => 'TARIFA ESPECIAL PARA HIJO DE TRABAJADOR UNU',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'id_cpg' => 6,
                'codigo_cpg' => 'EXO_CEP',
                'nombre_cpg' => 'EXONERACION CEPREUNU',
                'descripcion_cpg' => 'EXONERACION O TARIFA ESPECIAL CEPREUNU',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
        ];
    }
}
