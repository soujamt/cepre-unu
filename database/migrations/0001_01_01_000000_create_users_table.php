<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuario', function (Blueprint $table): void {
            $table->bigIncrements('id_usu');
            $table->string('usuario_usu', 50);
            $table->string('clave_usu');
            $table->string('correo_usu', 150)->nullable();
            $table->string('nombres_usu', 150);
            $table->enum('estado_usu', ['HABILITADO', 'DESHABILITADO', 'BLOQUEADO'])->default('HABILITADO');
            $table->rememberToken();
            $this->addAuditColumns($table);
            $table->unique('usuario_usu', 'uk_usuario_usu');
            $table->unique('correo_usu', 'uk_correo_usu');
            $table->index('estado_usu', 'idx_usuario_estado');
        });

        $this->addAuditForeignKeys('tbl_usuario');
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_usuario');
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
