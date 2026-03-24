<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_modalidad_admision', function (Blueprint $table): void {
            $table->tinyIncrements('id_mad');
            $table->string('codigo_mad');
            $table->string('nombre_mad');
            $table->text('descripcion_mad')->nullable();
            $table->enum('estado_mad', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_mad', 'uk_codigo_mad');
            $table->unique('nombre_mad', 'uk_nombre_mad');
        });

        Schema::create('tbl_sede', function (Blueprint $table): void {
            $table->increments('id_sed');
            $table->string('codigo_sed');
            $table->string('nombre_sed');
            $table->char('id_ubigeo', 6);
            $table->string('direccion_sed')->nullable();
            $table->enum('estado_sed', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_sed', 'uk_codigo_sed');
            $table->unique('nombre_sed', 'uk_nombre_sed');
            $table->index(['id_ubigeo', 'estado_sed'], 'idx_sede_ubigeo_estado');
        });

        Schema::create('tbl_lugar_inscripcion', function (Blueprint $table): void {
            $table->increments('id_lin');
            $table->string('codigo_lin');
            $table->string('nombre_lin');
            $table->unsignedInteger('id_sede');
            $table->enum('estado_lin', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_lin', 'uk_codigo_lin');
            $table->unique('nombre_lin', 'uk_nombre_lin');
            $table->index(['id_sede', 'estado_lin'], 'idx_lugar_sede_estado');
        });

        Schema::create('tbl_carrera', function (Blueprint $table): void {
            $table->increments('id_car');
            $table->string('codigo_car');
            $table->string('nombre_car');
            $table->enum('estado_car', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_car', 'uk_codigo_car');
            $table->unique('nombre_car', 'uk_nombre_car');
        });

        Schema::create('tbl_turno', function (Blueprint $table): void {
            $table->tinyIncrements('id_tur');
            $table->string('codigo_tur');
            $table->string('nombre_tur');
            $table->enum('estado_tur', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_tur', 'uk_codigo_tur');
            $table->unique('nombre_tur', 'uk_nombre_tur');
        });

        Schema::create('tbl_semestre', function (Blueprint $table): void {
            $table->increments('id_sem');
            $table->string('codigo_sem');
            $table->string('nombre_sem');
            $table->date('fecha_inicio_inscripcion_sem');
            $table->date('fecha_fin_inscripcion_sem');
            $table->enum('estado_sem', ['HABILITADO', 'DESHABILITADO', 'CERRADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_sem', 'uk_codigo_sem');
            $table->unique('nombre_sem', 'uk_nombre_sem');
            $table->index(['estado_sem', 'fecha_inicio_inscripcion_sem', 'fecha_fin_inscripcion_sem'], 'idx_semestre_estado_fechas');
        });

        Schema::create('tbl_proceso', function (Blueprint $table): void {
            $table->increments('id_pro');
            $table->unsignedInteger('id_semestre');
            $table->string('codigo_pro');
            $table->string('nombre_pro');
            $table->date('fecha_inicio_pro');
            $table->date('fecha_fin_pro');
            $table->enum('estado_pro', ['HABILITADO', 'DESHABILITADO', 'CERRADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_pro', 'uk_codigo_pro');
            $table->index(['id_semestre', 'estado_pro'], 'idx_proceso_semestre_estado');
            $table->index(['fecha_inicio_pro', 'fecha_fin_pro'], 'idx_proceso_fechas');
        });

        $this->addAuditForeignKeys('tbl_modalidad_admision');
        $this->addAuditForeignKeys('tbl_sede');
        $this->addAuditForeignKeys('tbl_lugar_inscripcion');
        $this->addAuditForeignKeys('tbl_carrera');
        $this->addAuditForeignKeys('tbl_turno');
        $this->addAuditForeignKeys('tbl_semestre');
        $this->addAuditForeignKeys('tbl_proceso');

        Schema::table('tbl_lugar_inscripcion', function (Blueprint $table): void {
            $table->foreign('id_sede', 'fk_lugar_inscripcion_sede')->references('id_sed')->on('tbl_sede')->restrictOnDelete();
        });

        Schema::table('tbl_sede', function (Blueprint $table): void {
            $table->foreign('id_ubigeo', 'fk_sede_ubigeo')->references('id_ubi')->on('tbl_ubigeo')->restrictOnDelete();
        });

        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->foreign('id_semestre', 'fk_proceso_semestre')->references('id_sem')->on('tbl_semestre')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proceso');
        Schema::dropIfExists('tbl_semestre');
        Schema::dropIfExists('tbl_turno');
        Schema::dropIfExists('tbl_carrera');
        Schema::dropIfExists('tbl_lugar_inscripcion');
        Schema::dropIfExists('tbl_sede');
        Schema::dropIfExists('tbl_modalidad_admision');
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
