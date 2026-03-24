<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_tipo_expediente', function (Blueprint $table): void {
            $table->tinyIncrements('id_tex');
            $table->string('codigo_tex');
            $table->string('nombre_tex');
            $table->text('descripcion_tex')->nullable();
            $table->boolean('obligatorio_tex')->default(true);
            $table->enum('estado_tex', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_tex', 'uk_codigo_tex');
            $table->unique('nombre_tex', 'uk_nombre_tex');
        });

        Schema::create('tbl_requisito_expediente', function (Blueprint $table): void {
            $table->bigIncrements('id_rex');
            $table->unsignedInteger('id_proceso');
            $table->unsignedTinyInteger('id_tipo_expediente');
            $table->boolean('obligatorio_rex')->default(true);
            $table->enum('estado_rex', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_proceso', 'id_tipo_expediente'], 'uk_requisito_expediente');
            $table->index(['id_proceso', 'estado_rex'], 'idx_requisito_expediente_estado');
        });

        Schema::create('tbl_inscripcion_expediente', function (Blueprint $table): void {
            $table->bigIncrements('id_iex');
            $table->foreignId('id_inscripcion')->constrained('tbl_inscripcion', 'id_ins')->restrictOnDelete();
            $table->unsignedTinyInteger('id_tipo_expediente');
            $table->string('archivo_url_iex')->nullable();
            $table->string('nombre_archivo_iex')->nullable();
            $table->enum('estado_revision_iex', ['PENDIENTE', 'APROBADO', 'OBSERVADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->string('observacion_iex')->nullable();
            $table->dateTime('revisado_en')->nullable();
            $table->foreignId('id_usuario_revisor')->nullable()->constrained('tbl_usuario', 'id_usu')->restrictOnDelete();
            $this->addAuditColumns($table);
            $table->unique(['id_inscripcion', 'id_tipo_expediente'], 'uk_inscripcion_expediente');
            $table->index(['id_inscripcion', 'estado_revision_iex'], 'idx_inscripcion_expediente_estado');
        });

        $this->addAuditForeignKeys('tbl_tipo_expediente');
        $this->addAuditForeignKeys('tbl_requisito_expediente');
        $this->addAuditForeignKeys('tbl_inscripcion_expediente');

        Schema::table('tbl_requisito_expediente', function (Blueprint $table): void {
            $table->foreign('id_proceso', 'fk_requisito_expediente_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_tipo_expediente', 'fk_requisito_expediente_tipo')->references('id_tex')->on('tbl_tipo_expediente')->restrictOnDelete();
        });

        Schema::table('tbl_inscripcion_expediente', function (Blueprint $table): void {
            $table->foreign('id_tipo_expediente', 'fk_inscripcion_expediente_tipo')->references('id_tex')->on('tbl_tipo_expediente')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inscripcion_expediente');
        Schema::dropIfExists('tbl_requisito_expediente');
        Schema::dropIfExists('tbl_tipo_expediente');
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
