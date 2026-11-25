<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las carreras del proceso se ofrecen por sede y por modalidad: una carrera puede
     * estar en ORDINARIO y no en EXTRAORDINARIO de la misma sede. La oferta actual se
     * copia a cada modalidad habilitada del proceso, así nada deja de ofrecerse.
     */
    public function up(): void
    {
        // La llave única sin modalidad impediría copiar la carrera a otra modalidad.
        Schema::table('tbl_proceso_carrera', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id_modalidad_admision')->nullable()->after('id_sede');
            $table->dropUnique('uk_proceso_carrera');
        });

        $modalidadesConCondicion = DB::table('tbl_modalidad_admision')
            ->whereNotNull('condicion_egreso_mad')
            ->whereNull('eliminado_en')
            ->orderBy('id_mad')
            ->pluck('id_mad');

        DB::table('tbl_proceso_carrera')->orderBy('id_pca')->get()->each(function (object $oferta) use ($modalidadesConCondicion): void {
            $modalidades = DB::table('tbl_proceso_modalidad')
                ->where('id_proceso', $oferta->id_proceso)
                ->where('estado_pmo', 'HABILITADO')
                ->whereNull('eliminado_en')
                ->orderBy('id_modalidad_admision')
                ->pluck('id_modalidad_admision');

            if ($modalidades->isEmpty()) {
                $modalidades = $modalidadesConCondicion;
            }

            if ($modalidades->isEmpty()) {
                DB::table('tbl_proceso_carrera')->where('id_pca', $oferta->id_pca)->delete();

                return;
            }

            DB::table('tbl_proceso_carrera')->where('id_pca', $oferta->id_pca)->update(['id_modalidad_admision' => $modalidades->first()]);

            foreach ($modalidades->slice(1) as $modalidad) {
                $copia = (array) $oferta;
                unset($copia['id_pca']);
                DB::table('tbl_proceso_carrera')->insert([...$copia, 'id_modalidad_admision' => $modalidad]);
            }
        });

        Schema::table('tbl_proceso_carrera', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id_modalidad_admision')->nullable(false)->change();
            $table->unique(['id_proceso', 'id_sede', 'id_modalidad_admision', 'id_carrera'], 'uk_proceso_carrera_modalidad');
            $table->foreign('id_modalidad_admision', 'fk_proceso_carrera_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
        });
    }

    /**
     * Vuelve a una fila por sede y carrera: queda habilitada si lo estaba en alguna modalidad.
     */
    public function down(): void
    {
        $grupos = DB::table('tbl_proceso_carrera')->orderBy('id_pca')->get()->groupBy(fn (object $oferta): string => "{$oferta->id_proceso}|{$oferta->id_sede}|{$oferta->id_carrera}");

        foreach ($grupos as $ofertas) {
            $conservar = $ofertas->first();
            $habilitada = $ofertas->contains(fn (object $oferta): bool => $oferta->estado_pca === 'HABILITADO');

            DB::table('tbl_proceso_carrera')->where('id_pca', $conservar->id_pca)->update(['estado_pca' => $habilitada ? 'HABILITADO' : 'DESHABILITADO']);
            DB::table('tbl_proceso_carrera')->whereIn('id_pca', $ofertas->slice(1)->pluck('id_pca'))->delete();
        }

        Schema::table('tbl_proceso_carrera', function (Blueprint $table): void {
            $table->dropForeign('fk_proceso_carrera_modalidad');
            $table->dropUnique('uk_proceso_carrera_modalidad');
            $table->dropColumn('id_modalidad_admision');
            $table->unique(['id_proceso', 'id_sede', 'id_carrera'], 'uk_proceso_carrera');
        });
    }
};
