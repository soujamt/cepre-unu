<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_proceso_aula', function (Blueprint $table): void {
            $table->bigIncrements('id_pau');
            $table->unsignedInteger('id_proceso');
            $table->unsignedBigInteger('id_aula');
            $table->integer('capacidad_pau')->default(0);
            $table->enum('estado_pau', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);

            $table->foreign('id_proceso', 'tbl_proceso_aula_fk_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
            $table->foreign('id_aula', 'tbl_proceso_aula_fk_aula')->references('id_aul')->on('tbl_aula')->restrictOnDelete();
            $table->unique(['id_proceso', 'id_aula'], 'uk_proceso_aula');
            $table->index('estado_pau', 'idx_proceso_aula_estado');
        });

        $this->addAuditForeignKeys('tbl_proceso_aula');
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proceso_aula');
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
