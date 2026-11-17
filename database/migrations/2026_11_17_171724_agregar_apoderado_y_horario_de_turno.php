<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contacto de emergencia del postulante (apoderado) y horario de cada turno.
     */
    public function up(): void
    {
        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->string('nombre_apoderado_per')->nullable()->after('correo_electronico_per');
            $table->string('celular_apoderado_per', 20)->nullable()->after('nombre_apoderado_per');
        });

        Schema::table('tbl_turno', function (Blueprint $table): void {
            $table->time('hora_inicio_tur')->nullable()->after('nombre_tur');
            $table->time('hora_fin_tur')->nullable()->after('hora_inicio_tur');
        });

        foreach (['MAN' => ['07:00', '12:00'], 'TAR' => ['13:00', '17:00'], 'NOC' => ['17:00', '22:00']] as $codigo => [$inicio, $fin]) {
            DB::table('tbl_turno')
                ->where('codigo_tur', $codigo)
                ->whereNull('hora_inicio_tur')
                ->update(['hora_inicio_tur' => $inicio, 'hora_fin_tur' => $fin]);
        }
    }

    public function down(): void
    {
        Schema::table('tbl_turno', function (Blueprint $table): void {
            $table->dropColumn(['hora_inicio_tur', 'hora_fin_tur']);
        });

        Schema::table('tbl_persona', function (Blueprint $table): void {
            $table->dropColumn(['nombre_apoderado_per', 'celular_apoderado_per']);
        });
    }
};
