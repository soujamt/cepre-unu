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
                'codigo_cpg' => 'INS_UNA',
                'nombre_cpg' => 'INSCRIPCION UNA CUOTA',
                'descripcion_cpg' => 'Inscripcion pagada en una sola cuota',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'codigo_cpg' => 'INS_CUO',
                'nombre_cpg' => 'INSCRIPCION MAS CUOTAS',
                'descripcion_cpg' => 'Inscripcion pagada en varias cuotas',
                'permite_cuotas_cpg' => true,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'codigo_cpg' => 'DUP_CAR',
                'nombre_cpg' => 'DUPLICADO CARNET / CAMBIO CARRERA',
                'descripcion_cpg' => 'Duplicado de carnet o cambio de carrera',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'codigo_cpg' => 'GUIA',
                'nombre_cpg' => 'GUIA',
                'descripcion_cpg' => 'Pago por guia',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'codigo_cpg' => 'HIJ_ADM',
                'nombre_cpg' => 'DESCUENTO HIJO ADMINISTRATIVO',
                'descripcion_cpg' => 'Tarifa especial para hijo de trabajador UNU',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
            [
                'codigo_cpg' => 'EXO_CEP',
                'nombre_cpg' => 'EXONERACION CEPREUNU',
                'descripcion_cpg' => 'Exoneracion o tarifa especial CEPREUNU',
                'permite_cuotas_cpg' => false,
                'estado_cpg' => 'HABILITADO',
            ],
        ];
    }
}
