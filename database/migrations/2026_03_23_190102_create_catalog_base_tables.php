<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_ubigeo', function (Blueprint $table): void {
            $table->char('id_ubi', 6)->primary();
            $table->char('codigo_departamento_ubi', 2);
            $table->char('codigo_provincia_ubi', 2);
            $table->char('codigo_distrito_ubi', 2);
            $table->string('nombre_departamento_ubi');
            $table->string('nombre_provincia_ubi');
            $table->string('nombre_distrito_ubi');
            $table->enum('estado_ubi', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(
                ['codigo_departamento_ubi', 'codigo_provincia_ubi', 'codigo_distrito_ubi'],
                'uk_ubigeo_codigo'
            );
            $table->index('estado_ubi', 'idx_ubigeo_estado');
            $table->index(['codigo_departamento_ubi', 'codigo_provincia_ubi'], 'idx_ubigeo_departamento_provincia');
        });

        Schema::create('tbl_pais', function (Blueprint $table): void {
            $table->smallIncrements('id_pai');
            $table->string('codigo_pai');
            $table->string('nombre_pai');
            $table->enum('estado_pai', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_pai', 'uk_codigo_pai');
            $table->unique('nombre_pai', 'uk_nombre_pai');
        });

        Schema::create('tbl_nacionalidad', function (Blueprint $table): void {
            $table->smallIncrements('id_nac');
            $table->string('codigo_nac');
            $table->string('nombre_nac');
            $table->enum('estado_nac', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_nac', 'uk_codigo_nac');
            $table->unique('nombre_nac', 'uk_nombre_nac');
        });

        Schema::create('tbl_tipo_documento', function (Blueprint $table): void {
            $table->tinyIncrements('id_tdo');
            $table->string('codigo_tdo');
            $table->string('nombre_tdo');
            $table->unsignedTinyInteger('longitud_minima_tdo')->nullable();
            $table->unsignedTinyInteger('longitud_maxima_tdo')->nullable();
            $table->enum('estado_tdo', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_tdo', 'uk_codigo_tdo');
            $table->unique('nombre_tdo', 'uk_nombre_tdo');
        });

        Schema::create('tbl_tipo_discapacidad', function (Blueprint $table): void {
            $table->tinyIncrements('id_tdi');
            $table->string('codigo_tdi');
            $table->string('nombre_tdi');
            $table->enum('estado_tdi', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_tdi', 'uk_codigo_tdi');
            $table->unique('nombre_tdi', 'uk_nombre_tdi');
        });

        Schema::create('tbl_identidad_etnica', function (Blueprint $table): void {
            $table->tinyIncrements('id_iet');
            $table->string('codigo_iet');
            $table->string('nombre_iet');
            $table->enum('estado_iet', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_iet', 'uk_codigo_iet');
            $table->unique('nombre_iet', 'uk_nombre_iet');
        });

        Schema::create('tbl_lengua_nativa', function (Blueprint $table): void {
            $table->smallIncrements('id_lna');
            $table->string('codigo_lna');
            $table->string('nombre_lna');
            $table->enum('estado_lna', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_lna', 'uk_codigo_lna');
            $table->unique('nombre_lna', 'uk_nombre_lna');
        });

        Schema::create('tbl_lengua_extranjera', function (Blueprint $table): void {
            $table->smallIncrements('id_lex');
            $table->string('codigo_lex');
            $table->string('nombre_lex');
            $table->enum('estado_lex', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_lex', 'uk_codigo_lex');
            $table->unique('nombre_lex', 'uk_nombre_lex');
        });

        Schema::create('tbl_tipo_colegio', function (Blueprint $table): void {
            $table->tinyIncrements('id_tco');
            $table->string('codigo_tco');
            $table->string('nombre_tco');
            $table->enum('estado_tco', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_tco', 'uk_codigo_tco');
            $table->unique('nombre_tco', 'uk_nombre_tco');
        });

        Schema::create('tbl_colegio', function (Blueprint $table): void {
            $table->string('id_col', 10)->primary();
            $table->string('codigo_modular_col')->nullable();
            $table->string('nombre_col');
            $table->unsignedTinyInteger('id_tipo_colegio')->nullable();
            $table->char('id_ubigeo', 6)->nullable();
            $table->string('direccion_col')->nullable();
            $table->enum('estado_col', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->index('codigo_modular_col', 'idx_colegio_codigo_modular');
            $table->index('id_tipo_colegio', 'idx_colegio_tipo');
            $table->index('id_ubigeo', 'idx_colegio_ubigeo');
            $table->index('estado_col', 'idx_colegio_estado');
        });

        $this->addAuditForeignKeys('tbl_ubigeo');
        $this->addAuditForeignKeys('tbl_pais');
        $this->addAuditForeignKeys('tbl_nacionalidad');
        $this->addAuditForeignKeys('tbl_tipo_documento');
        $this->addAuditForeignKeys('tbl_tipo_discapacidad');
        $this->addAuditForeignKeys('tbl_identidad_etnica');
        $this->addAuditForeignKeys('tbl_lengua_nativa');
        $this->addAuditForeignKeys('tbl_lengua_extranjera');
        $this->addAuditForeignKeys('tbl_tipo_colegio');
        $this->addAuditForeignKeys('tbl_colegio');

        Schema::table('tbl_colegio', function (Blueprint $table): void {
            $table->foreign('id_tipo_colegio', 'fk_colegio_tipo')->references('id_tco')->on('tbl_tipo_colegio')->restrictOnDelete();
            $table->foreign('id_ubigeo', 'fk_colegio_ubigeo')->references('id_ubi')->on('tbl_ubigeo')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_colegio');
        Schema::dropIfExists('tbl_tipo_colegio');
        Schema::dropIfExists('tbl_lengua_extranjera');
        Schema::dropIfExists('tbl_lengua_nativa');
        Schema::dropIfExists('tbl_identidad_etnica');
        Schema::dropIfExists('tbl_tipo_discapacidad');
        Schema::dropIfExists('tbl_tipo_documento');
        Schema::dropIfExists('tbl_nacionalidad');
        Schema::dropIfExists('tbl_pais');
        Schema::dropIfExists('tbl_ubigeo');
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
