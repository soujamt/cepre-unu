<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina los convenios: parentesco y becas se resuelven como modalidades de pago.
     */
    public function up(): void
    {
        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->dropIndex('idx_regla_pago_contexto_detallado');
            $table->dropForeign(['id_convenio_proceso']);
            $table->dropColumn('id_convenio_proceso');
        });

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->dropForeign(['id_convenio_proceso']);
            $table->dropIndex('idx_inscripcion_convenio_proceso');
            $table->dropColumn('id_convenio_proceso');
        });

        Schema::dropIfExists('tbl_convenio_proceso');
        Schema::dropIfExists('tbl_convenio');

        $idsPermiso = DB::table('tbl_permiso')
            ->where('clave_permiso', 'convenio')
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
