<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_importacion_banco', function (Blueprint $table): void {
            $table->string('hash_archivo_iba', 64)->nullable()->after('nombre_archivo_iba');
            $table->unique('hash_archivo_iba', 'uk_hash_archivo_iba');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_importacion_banco', function (Blueprint $table): void {
            $table->dropUnique('uk_hash_archivo_iba');
            $table->dropColumn('hash_archivo_iba');
        });
    }
};
