<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $permisos = ['tipo_expediente', 'requisito_expediente'];

    /**
     * Elimina los expedientes: la inscripción se activa con el pago validado.
     */
    public function up(): void
    {
        Schema::dropIfExists('tbl_inscripcion_expediente');
        Schema::dropIfExists('tbl_requisito_expediente');
        Schema::dropIfExists('tbl_tipo_expediente');

        $idsPermiso = DB::table('tbl_permiso')
            ->whereIn('clave_permiso', $this->permisos)
            ->pluck('id_permiso');

        $idsPermisoAccion = DB::table('tbl_permiso_accion')
            ->whereIn('id_permiso', $idsPermiso)
            ->pluck('id_peracc');

        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso')->whereIn('id_permiso', $idsPermiso)->delete();
    }

    /**
     * Los datos eliminados no se pueden recuperar.
     */
    public function down(): void {}
};
