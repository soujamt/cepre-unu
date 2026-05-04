<?php

namespace Database\Seeders;

use Database\Seeders\Support\CepreOfertaCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModalidadLugarInscripcionSeeder extends Seeder
{
    public function run(): void
    {
        $modalidadIds = DB::table('tbl_modalidad_admision')->pluck('id_mad', 'codigo_mad');
        $lugarIds = DB::table('tbl_lugar_inscripcion')->pluck('id_lin', 'codigo_lin');

        foreach (CepreOfertaCatalog::modalidadesLugarInscripcion() as $record) {
            $payload = [
                'id_modalidad_admision' => $modalidadIds[$record['codigo_mad']],
                'id_lugar_inscripcion' => $lugarIds[$record['codigo_lin']],
            ];

            $existingId = DB::table('tbl_modalidad_lugar_inscripcion')
                ->where($payload)
                ->value('id_mli');

            if ($existingId !== null) {
                DB::table('tbl_modalidad_lugar_inscripcion')
                    ->where('id_mli', $existingId)
                    ->update([
                        'estado_mli' => $record['estado_mli'],
                    ]);

                continue;
            }

            DB::table('tbl_modalidad_lugar_inscripcion')->insert([
                'id_mli' => $record['id_mli'],
                ...$payload,
                'estado_mli' => $record['estado_mli'],
            ]);
        }
    }
}
