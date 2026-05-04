<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->boolean('solo_un_apellido_per')->default(false)->after('numero_documento_per');
            $table->string('apellido_casada_per')->nullable()->after('apellido_materno_per');
            $table->enum('tipo_lengua_materna_per', ['NATIVA', 'EXTRANJERA'])->nullable()->after('id_tipo_discapacidad');
            $table->index('solo_un_apellido_per', 'idx_persona_solo_un_apellido');
            $table->index('tipo_lengua_materna_per', 'idx_persona_tipo_lengua_materna');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->dropIndex('idx_persona_tipo_lengua_materna');
            $table->dropIndex('idx_persona_solo_un_apellido');
            $table->dropColumn([
                'solo_un_apellido_per',
                'apellido_casada_per',
                'tipo_lengua_materna_per',
            ]);
        });
    }
};
