<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id_estado_civil')->nullable()->after('sexo_per');
            $table->index('id_estado_civil', 'idx_persona_estado_civil');
            $table->foreign('id_estado_civil', 'fk_persona_estado_civil')->references('id_eci')->on('tbl_estado_civil')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->dropForeign('fk_persona_estado_civil');
            $table->dropIndex('idx_persona_estado_civil');
            $table->dropColumn('id_estado_civil');
        });
    }
};
