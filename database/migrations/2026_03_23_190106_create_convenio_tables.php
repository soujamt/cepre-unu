<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_convenio', function (Blueprint $table): void {
            $table->bigIncrements('id_con');
            $table->string('codigo_con');
            $table->string('nombre_con');
            $table->text('descripcion_con')->nullable();
            $table->enum('tipo_beneficio_con', ['EXONERACION_TOTAL', 'EXONERACION_PARCIAL', 'DESCUENTO'])->default('DESCUENTO');
            $table->decimal('monto_descuento_con', 10, 2)->nullable();
            $table->decimal('porcentaje_descuento_con', 5, 2)->nullable();
            $table->enum('estado_con', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_con', 'uk_codigo_con');
            $table->unique('nombre_con', 'uk_nombre_con');
            $table->index('estado_con', 'idx_convenio_estado');
        });

        Schema::create('tbl_convenio_proceso', function (Blueprint $table): void {
            $table->bigIncrements('id_cpr');
            $table->foreignId('id_convenio')->constrained('tbl_convenio', 'id_con')->restrictOnDelete();
            $table->unsignedInteger('id_proceso');
            $table->date('fecha_inicio_cpr');
            $table->date('fecha_fin_cpr');
            $table->unsignedInteger('cupo_maximo_cpr')->default(0);
            $table->unsignedInteger('cupo_usado_cpr')->default(0);
            $table->enum('estado_cpr', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_convenio', 'id_proceso'], 'uk_convenio_proceso');
            $table->index(['id_proceso', 'estado_cpr'], 'idx_convenio_proceso_estado');
            $table->index(['fecha_inicio_cpr', 'fecha_fin_cpr'], 'idx_convenio_proceso_fechas');
        });

        $this->addAuditForeignKeys('tbl_convenio');
        $this->addAuditForeignKeys('tbl_convenio_proceso');

        Schema::table('tbl_convenio_proceso', function (Blueprint $table): void {
            $table->foreign('id_proceso', 'fk_convenio_proceso_proceso')->references('id_pro')->on('tbl_proceso')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_convenio_proceso');
        Schema::dropIfExists('tbl_convenio');
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
