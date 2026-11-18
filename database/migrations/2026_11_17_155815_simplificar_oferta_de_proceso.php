<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $permisosEliminados = ['lugar_inscripcion', 'oferta_base'];

    /**
     * La oferta deja de depender de los códigos de Admisión: cada proceso
     * habilita sus modalidades y sus carreras por sede, y la inscripción guarda
     * directamente sede, modalidad, carrera y turno. Las ofertas base, lugares
     * y modalidades de Admisión quedan como histórico.
     */
    public function up(): void
    {
        $this->actualizarModalidades();

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->unsignedSmallInteger('anio_egreso_max_ordinario_pro')->nullable()->after('fecha_fin_pro');
        });

        Schema::create('tbl_proceso_modalidad', function (Blueprint $table): void {
            $table->bigIncrements('id_pmo');
            $table->unsignedInteger('id_proceso');
            $table->unsignedTinyInteger('id_modalidad_admision');
            $table->enum('estado_pmo', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);

            $table->foreign('id_proceso', 'fk_proceso_modalidad_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_modalidad_admision', 'fk_proceso_modalidad_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
            $table->unique(['id_proceso', 'id_modalidad_admision'], 'uk_proceso_modalidad');
        });

        Schema::create('tbl_proceso_carrera', function (Blueprint $table): void {
            $table->bigIncrements('id_pca');
            $table->unsignedInteger('id_proceso');
            $table->unsignedInteger('id_sede');
            $table->unsignedInteger('id_carrera');
            $table->enum('estado_pca', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);

            $table->foreign('id_proceso', 'fk_proceso_carrera_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_sede', 'fk_proceso_carrera_sede')->references('id_sed')->on('tbl_sede')->restrictOnDelete();
            $table->foreign('id_carrera', 'fk_proceso_carrera_carrera')->references('id_car')->on('tbl_carrera')->restrictOnDelete();
            $table->unique(['id_proceso', 'id_sede', 'id_carrera'], 'uk_proceso_carrera');
            $table->index(['id_proceso', 'estado_pca'], 'idx_proceso_carrera_estado');
        });

        $this->addAuditForeignKeys('tbl_proceso_modalidad');
        $this->addAuditForeignKeys('tbl_proceso_carrera');

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->unsignedInteger('id_proceso')->nullable()->after('id_persona');
            $table->unsignedInteger('id_sede')->nullable()->after('id_proceso');
            $table->unsignedTinyInteger('id_modalidad_admision')->nullable()->after('id_sede');
            $table->unsignedInteger('id_carrera')->nullable()->after('id_modalidad_admision');
            $table->unsignedTinyInteger('id_turno')->nullable()->after('id_carrera');

            $table->foreign('id_proceso', 'fk_inscripcion_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_sede', 'fk_inscripcion_sede')->references('id_sed')->on('tbl_sede')->restrictOnDelete();
            $table->foreign('id_modalidad_admision', 'fk_inscripcion_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
            $table->foreign('id_carrera', 'fk_inscripcion_carrera')->references('id_car')->on('tbl_carrera')->restrictOnDelete();
            $table->foreign('id_turno', 'fk_inscripcion_turno')->references('id_tur')->on('tbl_turno')->restrictOnDelete();
            $table->index(['id_persona', 'id_proceso'], 'idx_inscripcion_persona_proceso');
            $table->index(['id_proceso', 'id_sede', 'id_carrera'], 'idx_inscripcion_proceso_sede_carrera');
        });

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->dropUnique('uk_inscripcion_persona_oferta');
            $table->unsignedBigInteger('id_proceso_oferta')->nullable()->change();
        });

        $this->trasladarOfertasExistentes();
        $this->eliminarPermisos();
    }

    /**
     * Los cambios de estructura no se revierten: las ofertas quedan como histórico.
     */
    public function down(): void {}

    private function actualizarModalidades(): void
    {
        Schema::table('tbl_modalidad_admision', function (Blueprint $table): void {
            $table->enum('condicion_egreso_mad', ['EGRESADO', 'EN_COLEGIO'])->nullable()->after('descripcion_mad');
        });

        DB::table('tbl_modalidad_admision')
            ->whereNull('condicion_egreso_mad')
            ->update(['estado_mad' => 'DESHABILITADO']);

        foreach ([
            ['codigo_mad' => 'ORD', 'nombre_mad' => 'ORDINARIO', 'descripcion_mad' => 'YA CULMINÓ LA SECUNDARIA', 'condicion_egreso_mad' => 'EGRESADO'],
            ['codigo_mad' => 'EXT', 'nombre_mad' => 'EXTRAORDINARIO O RESERVA', 'descripcion_mad' => 'CURSA 4.° O 5.° DE SECUNDARIA', 'condicion_egreso_mad' => 'EN_COLEGIO'],
        ] as $modalidad) {
            DB::table('tbl_modalidad_admision')->updateOrInsert(
                ['codigo_mad' => $modalidad['codigo_mad']],
                [...$modalidad, 'estado_mad' => 'HABILITADO'],
            );
        }
    }

    /**
     * Copia a la inscripción y a la configuración del proceso los datos que
     * hoy viven en las ofertas base asignadas a cada proceso.
     */
    private function trasladarOfertasExistentes(): void
    {
        $ofertas = DB::table('tbl_proceso_oferta')
            ->join('tbl_oferta_base', 'tbl_oferta_base.id_ofb', '=', 'tbl_proceso_oferta.id_oferta_base')
            ->join('tbl_sede_carrera', 'tbl_sede_carrera.id_sca', '=', 'tbl_oferta_base.id_sede_carrera')
            ->join('tbl_modalidad_lugar_inscripcion', 'tbl_modalidad_lugar_inscripcion.id_mli', '=', 'tbl_oferta_base.id_modalidad_lugar_inscripcion')
            ->select([
                'tbl_proceso_oferta.id_pof',
                'tbl_proceso_oferta.id_proceso',
                'tbl_proceso_oferta.id_turno',
                'tbl_proceso_oferta.estado_pof',
                'tbl_sede_carrera.id_sede',
                'tbl_sede_carrera.id_carrera',
                'tbl_modalidad_lugar_inscripcion.id_modalidad_admision',
            ])
            ->get();

        foreach ($ofertas as $oferta) {
            DB::table('tbl_inscripcion')
                ->where('id_proceso_oferta', $oferta->id_pof)
                ->update([
                    'id_proceso' => $oferta->id_proceso,
                    'id_sede' => $oferta->id_sede,
                    'id_modalidad_admision' => $oferta->id_modalidad_admision,
                    'id_carrera' => $oferta->id_carrera,
                    'id_turno' => $oferta->id_turno,
                ]);

            if ($oferta->estado_pof !== 'HABILITADO') {
                continue;
            }

            DB::table('tbl_proceso_carrera')->insertOrIgnore([
                'id_proceso' => $oferta->id_proceso,
                'id_sede' => $oferta->id_sede,
                'id_carrera' => $oferta->id_carrera,
                'estado_pca' => 'HABILITADO',
            ]);
        }

        $idsModalidad = DB::table('tbl_modalidad_admision')
            ->whereIn('codigo_mad', ['ORD', 'EXT'])
            ->pluck('id_mad');

        foreach ($ofertas->where('estado_pof', 'HABILITADO')->pluck('id_proceso')->unique() as $idProceso) {
            foreach ($idsModalidad as $idModalidad) {
                DB::table('tbl_proceso_modalidad')->insertOrIgnore([
                    'id_proceso' => $idProceso,
                    'id_modalidad_admision' => $idModalidad,
                    'estado_pmo' => 'HABILITADO',
                ]);
            }
        }
    }

    private function eliminarPermisos(): void
    {
        $idsPermiso = DB::table('tbl_permiso')
            ->whereIn('clave_permiso', $this->permisosEliminados)
            ->pluck('id_permiso');

        $idsPermisoAccion = DB::table('tbl_permiso_accion')
            ->whereIn('id_permiso', $idsPermiso)
            ->pluck('id_peracc');

        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso')->whereIn('id_permiso', $idsPermiso)->delete();

        DB::table('tbl_permiso')
            ->where('clave_permiso', 'oferta')
            ->update(['nombre_permiso' => 'OFERTA DEL PROCESO']);
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
