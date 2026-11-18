<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{nombre: string, acciones: list<string>}>
     */
    private array $permisos = [
        'codigo_admision' => ['nombre' => 'CÓDIGOS DE ADMISIÓN', 'acciones' => ['indice', 'editar']],
    ];

    /**
     * Códigos que Admisión entrega al cierre de cada proceso. Sin carrera es
     * el código de lugar (sede + modalidad); con carrera, el de la oferta.
     */
    public function up(): void
    {
        Schema::create('tbl_proceso_codigo_admision', function (Blueprint $table): void {
            $table->id('id_pcd');
            $table->unsignedInteger('id_proceso');
            $table->unsignedInteger('id_sede');
            $table->unsignedTinyInteger('id_modalidad_admision');
            $table->unsignedInteger('id_carrera')->nullable();
            $table->string('codigo_pcd', 20);
            $this->addAuditColumns($table);

            $table->index(['id_proceso', 'id_sede', 'id_modalidad_admision', 'id_carrera'], 'idx_codigo_admision_combinacion');
            $table->foreign('id_proceso', 'fk_codigo_admision_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_sede', 'fk_codigo_admision_sede')->references('id_sed')->on('tbl_sede')->restrictOnDelete();
            $table->foreign('id_modalidad_admision', 'fk_codigo_admision_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
            $table->foreign('id_carrera', 'fk_codigo_admision_carrera')->references('id_car')->on('tbl_carrera')->restrictOnDelete();
        });

        $this->addAuditForeignKeys('tbl_proceso_codigo_admision');
        $this->registrarPermisos();
    }

    public function down(): void
    {
        $idsPermiso = DB::table('tbl_permiso')->whereIn('clave_permiso', array_keys($this->permisos))->pluck('id_permiso');
        $idsPermisoAccion = DB::table('tbl_permiso_accion')->whereIn('id_permiso', $idsPermiso)->pluck('id_peracc');
        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso')->whereIn('id_permiso', $idsPermiso)->delete();

        Schema::dropIfExists('tbl_proceso_codigo_admision');
    }

    private function registrarPermisos(): void
    {
        $idsAccion = DB::table('tbl_accion')->pluck('id_acc', 'clave_accion');

        foreach ($this->permisos as $clave => $permiso) {
            DB::table('tbl_permiso')->updateOrInsert(
                ['clave_permiso' => $clave],
                ['nombre_permiso' => $permiso['nombre'], 'estado_permiso' => 'HABILITADO', 'eliminado_en' => null],
            );

            $idPermiso = DB::table('tbl_permiso')->where('clave_permiso', $clave)->value('id_permiso');

            foreach ($permiso['acciones'] as $accion) {
                if (! isset($idsAccion[$accion])) {
                    continue;
                }

                DB::table('tbl_permiso_accion')->updateOrInsert(
                    ['clave_peracc' => "{$clave}_{$accion}"],
                    ['id_permiso' => $idPermiso, 'id_accion' => $idsAccion[$accion], 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
                );
            }
        }
    }

    private function addAuditColumns(Blueprint $table): void
    {
        $table->timestamp('creado_en')->useCurrent();
        $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        $table->timestamp('eliminado_en')->nullable();
        $table->unsignedBigInteger('id_usuario_creador')->nullable();
        $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
        $table->unsignedBigInteger('id_usuario_eliminador')->nullable();
    }

    private function addAuditForeignKeys(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->foreign('id_usuario_creador', "{$tableName}_fk_usuario_creador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_actualizador', "{$tableName}_fk_usuario_actualizador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_eliminador', "{$tableName}_fk_usuario_eliminador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
        });
    }
};
