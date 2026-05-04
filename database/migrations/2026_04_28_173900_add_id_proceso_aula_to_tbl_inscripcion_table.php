<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_proceso_aula')->nullable()->after('id_colegio');

            $table->foreign('id_proceso_aula', 'tbl_inscripcion_fk_proceso_aula')
                ->references('id_pau')->on('tbl_proceso_aula')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->dropForeign('tbl_inscripcion_fk_proceso_aula');
            $table->dropColumn('id_proceso_aula');
        });
    }
};
