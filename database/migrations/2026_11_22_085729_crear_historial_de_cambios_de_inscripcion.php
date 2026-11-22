<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de los cambios de postulación (sede, carrera, turno, aula) que el
     * personal hace sobre una inscripción ya registrada, con su motivo.
     */
    public function up(): void
    {
        Schema::create('tbl_inscripcion_cambio', function (Blueprint $table): void {
            $table->bigIncrements('id_ica');
            $table->unsignedBigInteger('id_inscripcion');
            $table->json('cambios_ica');
            $table->string('motivo_ica', 500);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('eliminado_en')->nullable();
            $table->unsignedBigInteger('id_usuario_creador')->nullable();
            $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
            $table->unsignedBigInteger('id_usuario_eliminador')->nullable();

            $table->index('id_inscripcion', 'idx_inscripcion_cambio_inscripcion');
            $table->foreign('id_inscripcion', 'fk_inscripcion_cambio_inscripcion')->references('id_ins')->on('tbl_inscripcion')->restrictOnDelete();
            $table->foreign('id_usuario_creador', 'tbl_inscripcion_cambio_fk_usuario_creador')->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_actualizador', 'tbl_inscripcion_cambio_fk_usuario_actualizador')->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_eliminador', 'tbl_inscripcion_cambio_fk_usuario_eliminador')->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inscripcion_cambio');
    }
};
