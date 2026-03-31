<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tbl_usuario')
            ->where('estado_usu', 'BLOQUEADO')
            ->update([
                'estado_usu' => 'DESHABILITADO',
            ]);

        Schema::table('tbl_usuario', function (Blueprint $table): void {
            $table->enum('estado_usu', ['HABILITADO', 'DESHABILITADO'])
                ->default('HABILITADO')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_usuario', function (Blueprint $table): void {
            $table->enum('estado_usu', ['HABILITADO', 'DESHABILITADO', 'BLOQUEADO'])
                ->default('HABILITADO')
                ->change();
        });
    }
};
