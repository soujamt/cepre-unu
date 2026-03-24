<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_inscripcion', function (Blueprint $table): void {
            $table->bigIncrements('id_ins');
            $table->string('codigo_ins');
            $table->foreignId('id_persona')->constrained('tbl_persona', 'id_per')->restrictOnDelete();
            $table->foreignId('id_proceso_oferta')->constrained('tbl_proceso_oferta', 'id_pof')->restrictOnDelete();
            $table->foreignId('id_convenio_proceso')->nullable()->constrained('tbl_convenio_proceso', 'id_cpr')->restrictOnDelete();
            $table->dateTime('fecha_inscripcion_ins')->useCurrent();
            $table->enum('estado_ins', ['PENDIENTE', 'ACTIVO', 'ANULADO'])->default('PENDIENTE');
            $table->string('motivo_anulacion_ins')->nullable();
            $table->unsignedSmallInteger('anio_graduacion_ins')->nullable();
            $table->unsignedSmallInteger('id_pais_colegio')->nullable();
            $table->string('id_colegio', 10)->nullable();
            $table->string('nombre_colegio_extranjero_ins')->nullable();
            $table->unsignedTinyInteger('id_tipo_colegio')->nullable();
            $table->unsignedSmallInteger('veces_postula_unu_ins')->default(0);
            $table->unsignedSmallInteger('veces_postula_otras_ins')->default(0);
            $table->text('observacion_ins')->nullable();
            $this->addAuditColumns($table);
            $table->unique('codigo_ins', 'uk_codigo_ins');
            $table->unique(['id_persona', 'id_proceso_oferta'], 'uk_inscripcion_persona_oferta');
            $table->index(['estado_ins', 'fecha_inscripcion_ins'], 'idx_inscripcion_estado_fecha');
            $table->index('id_convenio_proceso', 'idx_inscripcion_convenio_proceso');
            $table->index('id_persona', 'idx_inscripcion_persona');
        });

        $this->addAuditForeignKeys('tbl_inscripcion');

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->foreign('id_pais_colegio', 'fk_inscripcion_pais_colegio')->references('id_pai')->on('tbl_pais')->restrictOnDelete();
            $table->foreign('id_colegio', 'fk_inscripcion_colegio')->references('id_col')->on('tbl_colegio')->restrictOnDelete();
            $table->foreign('id_tipo_colegio', 'fk_inscripcion_tipo_colegio')->references('id_tco')->on('tbl_tipo_colegio')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inscripcion');
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
