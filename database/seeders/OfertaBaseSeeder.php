<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfertaBaseSeeder extends Seeder
{
    public function run(): void
    {
        $sedeIds = DB::table('tbl_sede')->pluck('id_sed', 'codigo_sed');
        $carreraIds = DB::table('tbl_carrera')->pluck('id_car', 'nombre_car');
        $modalidadIds = DB::table('tbl_modalidad_admision')->pluck('id_mad', 'codigo_mad');
        $lugarIds = DB::table('tbl_lugar_inscripcion')->pluck('id_lin', 'codigo_lin');

        foreach (CepreOfertaCatalog::ofertas() as $record) {
            $sedeId = $sedeIds[$record['codigo_sed']];
            $carreraId = $carreraIds[$record['nombre_car']];
            $sedeCarreraId = DB::table('tbl_sede_carrera')
                ->where('id_sede', $sedeId)
                ->where('id_carrera', $carreraId)
                ->value('id_sca');
            $modalidadLugarInscripcionId = DB::table('tbl_modalidad_lugar_inscripcion')
                ->where('id_modalidad_admision', $modalidadIds[$record['codigo_mad']])
                ->where('id_lugar_inscripcion', $lugarIds[$record['codigo_lin']])
                ->value('id_mli');

            DB::table('tbl_oferta_base')->updateOrInsert(
                ['codigo_externo_ofb' => $record['codigo_ofb']],
                [
                    'id_ofb' => (int) $record['codigo_ofb'],
                    'codigo_externo_ofb' => $record['codigo_ofb'],
                    'id_modalidad_lugar_inscripcion' => $modalidadLugarInscripcionId,
                    'id_sede_carrera' => $sedeCarreraId,
                    'estado_ofb' => 'HABILITADO',
                ],
            );
        }
    }
}
