<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_lugar_inscripcion', function (Blueprint $table): void {
            $table->dropUnique('uk_nombre_lin');
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->string('codigo_externo_ofb')->nullable()->after('id_ofb');
            $table->unique('codigo_externo_ofb', 'uk_codigo_externo_ofb');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->dropUnique('uk_codigo_externo_ofb');
            $table->dropColumn('codigo_externo_ofb');
        });

        Schema::table('tbl_lugar_inscripcion', function (Blueprint $table): void {
            $table->unique('nombre_lin', 'uk_nombre_lin');
        });
    }
};
