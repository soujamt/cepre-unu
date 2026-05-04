<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('tbl_accion')
            ->where('clave_accion', 'permisos')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('tbl_accion')->insert([
            'nombre_accion' => 'Permisos',
            'clave_accion' => 'permisos',
            'estado_accion' => 'HABILITADO',
        ]);
    }

    public function down(): void
    {
        DB::table('tbl_accion')
            ->where('clave_accion', 'permisos')
            ->delete();
    }
};
