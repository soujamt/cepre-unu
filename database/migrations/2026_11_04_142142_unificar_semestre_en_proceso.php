<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina el concepto de semestre: cada proceso pasa a representar el periodo
 * de admisión (formato AAAA-N, por ejemplo 2026-2) y conserva sus propias
 * fechas de inscripción. Los procesos existentes toman el nombre de su
 * semestre y se eliminan la tabla tbl_semestre y los permisos del módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renombrarProcesosConSuSemestre();

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $this->dropForeignKey($table, 'fk_proceso_semestre', ['id_semestre']);
        });

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->dropIndex('idx_proceso_semestre_estado');
            $table->dropColumn('id_semestre');
            $table->index('estado_pro', 'idx_proceso_estado');
            $table->unique('nombre_pro', 'uk_nombre_pro');
        });

        $this->eliminarPermisosDeSemestre();

        Schema::dropIfExists('tbl_semestre');
    }

    public function down(): void
    {
        Schema::create('tbl_semestre', function (Blueprint $table): void {
            $table->increments('id_sem');
            $table->string('codigo_sem');
            $table->string('nombre_sem');
            $table->date('fecha_inicio_inscripcion_sem');
            $table->date('fecha_fin_inscripcion_sem');
            $table->enum('estado_sem', ['HABILITADO', 'DESHABILITADO', 'CERRADO'])->default('HABILITADO');
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('eliminado_en')->nullable();
            $table->unsignedBigInteger('id_usuario_creador')->nullable();
            $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
            $table->unsignedBigInteger('id_usuario_eliminador')->nullable();
            $table->unique('codigo_sem', 'uk_codigo_sem');
            $table->unique('nombre_sem', 'uk_nombre_sem');
            $table->index(['estado_sem', 'fecha_inicio_inscripcion_sem', 'fecha_fin_inscripcion_sem'], 'idx_semestre_estado_fechas');
        });

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->dropUnique('uk_nombre_pro');
            $table->dropIndex('idx_proceso_estado');
            $table->unsignedInteger('id_semestre')->nullable()->after('id_pro');
        });

        foreach (DB::table('tbl_proceso')->orderBy('id_pro')->get() as $proceso) {
            $idSemestre = DB::table('tbl_semestre')->insertGetId([
                'codigo_sem' => $proceso->codigo_pro,
                'nombre_sem' => $proceso->nombre_pro,
                'fecha_inicio_inscripcion_sem' => $proceso->fecha_inicio_pro,
                'fecha_fin_inscripcion_sem' => $proceso->fecha_fin_pro,
                'estado_sem' => $proceso->estado_pro,
            ]);

            DB::table('tbl_proceso')->where('id_pro', $proceso->id_pro)->update(['id_semestre' => $idSemestre]);
        }

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->index(['id_semestre', 'estado_pro'], 'idx_proceso_semestre_estado');
            $table->foreign('id_semestre', 'fk_proceso_semestre')->references('id_sem')->on('tbl_semestre')->restrictOnDelete();
        });
    }

    /**
     * El primer proceso de cada semestre toma su nombre (ej. 2026-1); si un
     * semestre tenía varios procesos, los siguientes llevan un sufijo.
     */
    private function renombrarProcesosConSuSemestre(): void
    {
        $procesos = DB::table('tbl_proceso')
            ->join('tbl_semestre', 'tbl_semestre.id_sem', '=', 'tbl_proceso.id_semestre')
            ->orderBy('tbl_semestre.id_sem')
            ->orderBy('tbl_proceso.id_pro')
            ->get(['tbl_proceso.id_pro', 'tbl_semestre.id_sem', 'tbl_semestre.nombre_sem']);

        // Primero códigos temporales para no chocar con la restricción única
        foreach ($procesos as $proceso) {
            DB::table('tbl_proceso')->where('id_pro', $proceso->id_pro)->update([
                'codigo_pro' => 'tmp_'.$proceso->id_pro,
                'nombre_pro' => 'tmp_'.$proceso->id_pro,
            ]);
        }

        $usadosPorSemestre = [];

        foreach ($procesos as $proceso) {
            $orden = ($usadosPorSemestre[$proceso->id_sem] ?? 0) + 1;
            $usadosPorSemestre[$proceso->id_sem] = $orden;

            $nombre = $orden === 1 ? $proceso->nombre_sem : $proceso->nombre_sem.'-'.$orden;

            DB::table('tbl_proceso')->where('id_pro', $proceso->id_pro)->update([
                'nombre_pro' => $nombre,
                'codigo_pro' => str_replace('-', '_', $nombre),
            ]);
        }
    }

    /**
     * SQLite no permite eliminar llaves foráneas por nombre; ahí se usan las columnas.
     *
     * @param  list<string>  $columns
     */
    private function dropForeignKey(Blueprint $table, string $indexName, array $columns): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $table->dropForeign($columns);

            return;
        }

        $table->dropForeign($indexName);
    }

    private function eliminarPermisosDeSemestre(): void
    {
        $idPermiso = DB::table('tbl_permiso')->where('clave_permiso', 'semestre')->value('id_permiso');

        if ($idPermiso === null) {
            return;
        }

        $idsPermisoAccion = DB::table('tbl_permiso_accion')->where('id_permiso', $idPermiso)->pluck('id_peracc');

        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->where('id_permiso', $idPermiso)->delete();
        DB::table('tbl_permiso')->where('id_permiso', $idPermiso)->delete();
    }
};
