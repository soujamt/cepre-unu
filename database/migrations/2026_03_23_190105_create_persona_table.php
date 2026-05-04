<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_persona', function (Blueprint $table): void {
            $table->bigIncrements('id_per');
            $table->unsignedTinyInteger('id_tipo_documento');
            $table->string('numero_documento_per');
            $table->string('apellido_paterno_per');
            $table->string('apellido_materno_per')->nullable();
            $table->string('nombres_per');
            $table->date('fecha_nacimiento_per')->nullable();
            $table->enum('sexo_per', ['MASCULINO', 'FEMENINO', 'OTRO'])->nullable();
            $table->unsignedSmallInteger('id_pais_nacimiento')->nullable();
            $table->unsignedSmallInteger('id_nacionalidad')->nullable();
            $table->char('id_ubigeo_nacimiento', 6)->nullable();
            $table->char('id_ubigeo_domicilio', 6)->nullable();
            $table->string('direccion_per')->nullable();
            $table->string('celular_per')->nullable();
            $table->string('telefono_per')->nullable();
            $table->string('correo_electronico_per')->nullable();
            $table->boolean('discapacidad_per')->default(false);
            $table->unsignedTinyInteger('id_tipo_discapacidad')->nullable();
            $table->unsignedTinyInteger('id_identidad_etnica')->nullable();
            $table->unsignedSmallInteger('id_lengua_nativa')->nullable();
            $table->unsignedSmallInteger('id_lengua_extranjera')->nullable();
            $table->text('observacion_per')->nullable();
            $table->enum('estado_per', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_tipo_documento', 'numero_documento_per'], 'uk_persona_documento');
            $table->index('correo_electronico_per', 'idx_persona_correo');
            $table->index('celular_per', 'idx_persona_celular');
            $table->index(['apellido_paterno_per', 'apellido_materno_per', 'nombres_per'], 'idx_persona_nombres');
            $table->index(['estado_per', 'id_tipo_documento'], 'idx_persona_estado_tipo_documento');
        });

        $this->addAuditForeignKeys('tbl_persona');

        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->foreign('id_tipo_documento', 'fk_persona_tipo_documento')->references('id_tdo')->on('tbl_tipo_documento')->restrictOnDelete();
            $table->foreign('id_pais_nacimiento', 'fk_persona_pais_nacimiento')->references('id_pai')->on('tbl_pais')->restrictOnDelete();
            $table->foreign('id_nacionalidad', 'fk_persona_nacionalidad')->references('id_nac')->on('tbl_nacionalidad')->restrictOnDelete();
            $table->foreign('id_ubigeo_nacimiento', 'fk_persona_ubigeo_nacimiento')->references('id_ubi')->on('tbl_ubigeo')->restrictOnDelete();
            $table->foreign('id_ubigeo_domicilio', 'fk_persona_ubigeo_domicilio')->references('id_ubi')->on('tbl_ubigeo')->restrictOnDelete();
            $table->foreign('id_tipo_discapacidad', 'fk_persona_tipo_discapacidad')->references('id_tdi')->on('tbl_tipo_discapacidad')->restrictOnDelete();
            $table->foreign('id_identidad_etnica', 'fk_persona_identidad_etnica')->references('id_iet')->on('tbl_identidad_etnica')->restrictOnDelete();
            $table->foreign('id_lengua_nativa', 'fk_persona_lengua_nativa')->references('id_lna')->on('tbl_lengua_nativa')->restrictOnDelete();
            $table->foreign('id_lengua_extranjera', 'fk_persona_lengua_extranjera')->references('id_lex')->on('tbl_lengua_extranjera')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_persona');
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
