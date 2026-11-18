<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las inscripciones hechas por el postulante en el portal quedan en
     * revisión y no se activan hasta que el personal las aprueba.
     */
    public function up(): void
    {
        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->enum('origen_ins', ['SISTEMA', 'PORTAL'])->default('SISTEMA')->after('estado_ins');
            $table->boolean('requiere_revision_ins')->default(false)->after('origen_ins');
            $table->timestamp('revisado_en')->nullable()->after('requiere_revision_ins');
            $table->unsignedBigInteger('id_usuario_revisor')->nullable()->after('revisado_en');

            $table->foreign('id_usuario_revisor', 'fk_inscripcion_usuario_revisor')->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->dropForeign('fk_inscripcion_usuario_revisor');
            $table->dropColumn(['origen_ins', 'requiere_revision_ins', 'revisado_en', 'id_usuario_revisor']);
        });
    }
};
