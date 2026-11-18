<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los pagos se registran también a mano (Admisión, Posgrado, parentesco)
     * y las modalidades de pago de beca o parentesco piden un documento.
     */
    public function up(): void
    {
        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_importacion_banco')->nullable()->change();
            $table->text('texto_linea_ibd')->nullable()->change();
            $table->enum('origen_ibd', ['TXT', 'MANUAL'])->default('TXT')->after('id_importacion_banco');
            $table->string('ubicacion_ibd')->nullable()->after('monto_ibd');
            $table->string('referencia_ibd')->nullable()->after('nombre_depositante_ibd');
            $table->string('observacion_ibd')->nullable()->after('estado_ibd');
            $table->index(['numero_documento_ibd', 'estado_ibd'], 'idx_ibd_documento_estado');
        });

        DB::table('tbl_importacion_banco_detalle')
            ->whereNull('ubicacion_ibd')
            ->update(['ubicacion_ibd' => 'RECAUDACIÓN BBVA N° 13248']);

        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->string('documento_requerido_rpa')->nullable()->after('requiere_pago_completo_rpa');
        });

        Schema::table('tbl_inscripcion_obligacion_pago', function (Blueprint $table): void {
            $table->string('numero_documento_beneficio_iop')->nullable()->after('numero_cuotas_iop');
        });

        $idAccion = DB::table('tbl_accion')->where('clave_accion', 'exportar_excel')->value('id_acc');
        $idPermiso = DB::table('tbl_permiso')->where('clave_permiso', 'pago')->value('id_permiso');

        if ($idAccion !== null && $idPermiso !== null) {
            DB::table('tbl_permiso_accion')->updateOrInsert(
                ['clave_peracc' => 'pago_exportar_excel'],
                ['id_permiso' => $idPermiso, 'id_accion' => $idAccion, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
            );
        }
    }

    public function down(): void
    {
        DB::table('tbl_permiso_accion')->where('clave_peracc', 'pago_exportar_excel')->delete();

        Schema::table('tbl_inscripcion_obligacion_pago', function (Blueprint $table): void {
            $table->dropColumn('numero_documento_beneficio_iop');
        });

        Schema::table('tbl_regla_pago', function (Blueprint $table): void {
            $table->dropColumn('documento_requerido_rpa');
        });

        Schema::table('tbl_importacion_banco_detalle', function (Blueprint $table): void {
            $table->dropIndex('idx_ibd_documento_estado');
            $table->dropColumn(['origen_ibd', 'ubicacion_ibd', 'referencia_ibd', 'observacion_ibd']);
        });
    }
};
