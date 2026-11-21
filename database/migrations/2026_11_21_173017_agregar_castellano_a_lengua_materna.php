<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega CASTELLANO como lengua materna del postulante, además de NATIVA y EXTRANJERA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->enum('tipo_lengua_materna_per', ['CASTELLANO', 'NATIVA', 'EXTRANJERA'])->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('tbl_persona')->where('tipo_lengua_materna_per', 'CASTELLANO')->update(['tipo_lengua_materna_per' => null]);

        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->enum('tipo_lengua_materna_per', ['NATIVA', 'EXTRANJERA'])->nullable()->change();
        });
    }
};
