<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las fechas del proceso pasan a incluir la hora exacta de apertura y cierre.
 * Los procesos existentes conservan su comportamiento: abren a las 00:00 del
 * día de inicio y cierran a las 23:59:59 del día final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->dateTime('fecha_inicio_pro')->change();
            $table->dateTime('fecha_fin_pro')->change();
        });

        foreach (DB::table('tbl_proceso')->get(['id_pro', 'fecha_inicio_pro', 'fecha_fin_pro']) as $proceso) {
            DB::table('tbl_proceso')->where('id_pro', $proceso->id_pro)->update([
                'fecha_inicio_pro' => substr((string) $proceso->fecha_inicio_pro, 0, 10).' 00:00:00',
                'fecha_fin_pro' => substr((string) $proceso->fecha_fin_pro, 0, 10).' 23:59:59',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tbl_proceso', function (Blueprint $table): void {
            $table->date('fecha_inicio_pro')->change();
            $table->date('fecha_fin_pro')->change();
        });
    }
};
