<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_sede_carrera', function (Blueprint $table): void {
            $table->bigIncrements('id_sca');
            $table->unsignedInteger('id_sede');
            $table->unsignedInteger('id_carrera');
            $table->string('codigo_externo_sca');
            $table->enum('estado_sca', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_sede', 'id_carrera'], 'uk_sede_carrera');
            $table->unique('codigo_externo_sca', 'uk_codigo_externo_sca');
            $table->index(['id_sede', 'estado_sca'], 'idx_sede_carrera_sede_estado');
        });

        Schema::create('tbl_oferta_base', function (Blueprint $table): void {
            $table->bigIncrements('id_ofb');
            $table->unsignedTinyInteger('id_modalidad_admision');
            $table->unsignedInteger('id_lugar_inscripcion');
            $table->unsignedBigInteger('id_sede_carrera');
            $table->enum('estado_ofb', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_modalidad_admision', 'id_lugar_inscripcion', 'id_sede_carrera'], 'uk_oferta_base');
            $table->index(['id_lugar_inscripcion', 'estado_ofb'], 'idx_oferta_base_lugar_estado');
        });

        Schema::create('tbl_proceso_oferta', function (Blueprint $table): void {
            $table->bigIncrements('id_pof');
            $table->unsignedInteger('id_proceso');
            $table->unsignedBigInteger('id_oferta_base');
            $table->unsignedTinyInteger('id_turno');
            $table->enum('estado_pof', ['HABILITADO', 'DESHABILITADO', 'CERRADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_proceso', 'id_oferta_base', 'id_turno'], 'uk_proceso_oferta');
            $table->index(['id_proceso', 'estado_pof'], 'idx_proceso_oferta_proceso_estado');
            $table->index(['id_oferta_base', 'id_turno'], 'idx_proceso_oferta_base_turno');
        });

        $this->addAuditForeignKeys('tbl_sede_carrera');
        $this->addAuditForeignKeys('tbl_oferta_base');
        $this->addAuditForeignKeys('tbl_proceso_oferta');

        Schema::table('tbl_sede_carrera', function (Blueprint $table): void {
            $table->foreign('id_sede', 'fk_sede_carrera_sede')->references('id_sed')->on('tbl_sede')->restrictOnDelete();
            $table->foreign('id_carrera', 'fk_sede_carrera_carrera')->references('id_car')->on('tbl_carrera')->restrictOnDelete();
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->foreign('id_modalidad_admision', 'fk_oferta_base_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
            $table->foreign('id_lugar_inscripcion', 'fk_oferta_base_lugar')->references('id_lin')->on('tbl_lugar_inscripcion')->restrictOnDelete();
            $table->foreign('id_sede_carrera', 'fk_oferta_base_sede_carrera')->references('id_sca')->on('tbl_sede_carrera')->restrictOnDelete();
        });

        Schema::table('tbl_proceso_oferta', function (Blueprint $table): void {
            $table->foreign('id_proceso', 'fk_proceso_oferta_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_oferta_base', 'fk_proceso_oferta_oferta_base')->references('id_ofb')->on('tbl_oferta_base')->restrictOnDelete();
            $table->foreign('id_turno', 'fk_proceso_oferta_turno')->references('id_tur')->on('tbl_turno')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proceso_oferta');
        Schema::dropIfExists('tbl_oferta_base');
        Schema::dropIfExists('tbl_sede_carrera');
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
