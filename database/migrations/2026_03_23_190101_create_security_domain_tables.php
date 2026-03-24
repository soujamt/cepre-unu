<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_rol', function (Blueprint $table): void {
            $table->bigIncrements('id_rol');
            $table->string('nombre_rol');
            $table->text('descripcion_rol')->nullable();
            $table->enum('estado_rol', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('nombre_rol', 'uk_nombre_rol');
            $table->index('estado_rol', 'idx_rol_estado');
        });

        Schema::create('tbl_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_acc');
            $table->string('codigo_acc');
            $table->string('nombre_acc');
            $table->string('modulo_acc');
            $table->text('descripcion_acc')->nullable();
            $table->enum('estado_acc', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_acc', 'uk_codigo_acc');
            $table->index(['modulo_acc', 'estado_acc'], 'idx_accion_modulo_estado');
        });

        Schema::create('tbl_usuario_rol', function (Blueprint $table): void {
            $table->bigIncrements('id_url');
            $table->foreignId('id_usuario');
            $table->foreignId('id_rol');
            $this->addAuditColumns($table);
            $table->unique(['id_usuario', 'id_rol'], 'uk_usuario_rol');
            $table->index('id_rol', 'idx_usuario_rol_rol');
        });

        Schema::create('tbl_rol_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_rac');
            $table->foreignId('id_rol');
            $table->foreignId('id_accion');
            $this->addAuditColumns($table);
            $table->unique(['id_rol', 'id_accion'], 'uk_rol_accion');
            $table->index('id_accion', 'idx_rol_accion_accion');
        });

        $this->addAuditForeignKeys('tbl_rol');
        $this->addAuditForeignKeys('tbl_accion');
        $this->addAuditForeignKeys('tbl_usuario_rol');
        $this->addAuditForeignKeys('tbl_rol_accion');

        Schema::table('tbl_usuario_rol', function (Blueprint $table): void {
            $table->foreign('id_usuario', 'fk_usuario_rol_usuario')->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_rol', 'fk_usuario_rol_rol')->references('id_rol')->on('tbl_rol')->restrictOnDelete();
        });

        Schema::table('tbl_rol_accion', function (Blueprint $table): void {
            $table->foreign('id_rol', 'fk_rol_accion_rol')->references('id_rol')->on('tbl_rol')->restrictOnDelete();
            $table->foreign('id_accion', 'fk_rol_accion_accion')->references('id_acc')->on('tbl_accion')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rol_accion');
        Schema::dropIfExists('tbl_usuario_rol');
        Schema::dropIfExists('tbl_accion');
        Schema::dropIfExists('tbl_rol');
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
