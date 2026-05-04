<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $idPermiso = DB::table('tbl_permiso')
            ->where('clave_permiso', 'reporte_inscripcion')
            ->value('id_permiso');

        $idAccion = DB::table('tbl_accion')
            ->where('clave_accion', 'exportar_excel')
            ->value('id_acc');

        if ($idPermiso === null || $idAccion === null) {
            return;
        }

        $existe = DB::table('tbl_permiso_accion')
            ->where('id_permiso', $idPermiso)
            ->where('id_accion', $idAccion)
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('tbl_permiso_accion')->insert([
            'id_permiso' => $idPermiso,
            'id_accion' => $idAccion,
            'clave_peracc' => 'reporte_inscripcion_exportar_excel',
            'estado_peracc' => 'HABILITADO',
        ]);
    }

    public function down(): void
    {
        DB::table('tbl_permiso_accion')
            ->where('clave_peracc', 'reporte_inscripcion_exportar_excel')
            ->delete();
    }
};
