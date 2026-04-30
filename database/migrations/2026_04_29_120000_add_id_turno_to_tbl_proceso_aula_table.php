<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_proceso_aula', 'id_turno')) {
            Schema::table('tbl_proceso_aula', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id_turno')->nullable()->after('id_proceso');
            });
        }

        if (! $this->tieneIndice('tbl_proceso_aula', 'idx_proceso_aula_proceso')) {
            Schema::table('tbl_proceso_aula', function (Blueprint $table): void {
                $table->index('id_proceso', 'idx_proceso_aula_proceso');
            });
        }

        if ($this->tieneIndice('tbl_proceso_aula', 'uk_proceso_aula')) {
            Schema::table('tbl_proceso_aula', function (Blueprint $table): void {
                $table->dropUnique('uk_proceso_aula');
            });
        }

        $this->replicarAsignacionesPorTurno();

        Schema::table('tbl_proceso_aula', function (Blueprint $table): void {
            if (! $this->tieneLlaveForanea('tbl_proceso_aula', 'tbl_proceso_aula_fk_turno')) {
                $table->foreign('id_turno', 'tbl_proceso_aula_fk_turno')->references('id_tur')->on('tbl_turno')->restrictOnDelete();
            }

            if (! $this->tieneIndice('tbl_proceso_aula', 'uk_proceso_aula')) {
                $table->unique(['id_proceso', 'id_turno', 'id_aula'], 'uk_proceso_aula');
            }

            if (! $this->tieneIndice('tbl_proceso_aula', 'idx_proceso_aula_proceso_turno_estado')) {
                $table->index(['id_proceso', 'id_turno', 'estado_pau'], 'idx_proceso_aula_proceso_turno_estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_proceso_aula', function (Blueprint $table): void {
            $table->dropUnique('uk_proceso_aula');
            $table->dropIndex('idx_proceso_aula_proceso_turno_estado');
            $table->dropForeign('tbl_proceso_aula_fk_turno');
            $table->dropColumn('id_turno');
            $table->unique(['id_proceso', 'id_aula'], 'uk_proceso_aula');
        });
    }

    private function replicarAsignacionesPorTurno(): void
    {
        $primerTurnoId = DB::table('tbl_turno')->orderBy('id_tur')->value('id_tur');

        if ($primerTurnoId === null) {
            return;
        }

        $mapeoAsignaciones = [];
        $asignaciones = DB::table('tbl_proceso_aula')
            ->whereNull('id_turno')
            ->orderBy('id_pau')
            ->get();

        foreach ($asignaciones as $asignacion) {
            $turnosProceso = DB::table('tbl_proceso_oferta')
                ->where('id_proceso', $asignacion->id_proceso)
                ->distinct()
                ->orderBy('id_turno')
                ->pluck('id_turno')
                ->filter()
                ->values();

            if ($turnosProceso->isEmpty()) {
                $turnosProceso = collect([$primerTurnoId]);
            }

            $turnoPrincipal = (int) $turnosProceso->first();

            DB::table('tbl_proceso_aula')
                ->where('id_pau', $asignacion->id_pau)
                ->update(['id_turno' => $turnoPrincipal]);

            $mapeoAsignaciones[$asignacion->id_pau][$turnoPrincipal] = $asignacion->id_pau;

            foreach ($turnosProceso->slice(1) as $turnoId) {
                $nuevoId = DB::table('tbl_proceso_aula')
                    ->where('id_proceso', $asignacion->id_proceso)
                    ->where('id_turno', $turnoId)
                    ->where('id_aula', $asignacion->id_aula)
                    ->value('id_pau');

                if ($nuevoId === null) {
                    $nuevoId = DB::table('tbl_proceso_aula')->insertGetId([
                        'id_proceso' => $asignacion->id_proceso,
                        'id_turno' => $turnoId,
                        'id_aula' => $asignacion->id_aula,
                        'capacidad_pau' => $asignacion->capacidad_pau,
                        'estado_pau' => $asignacion->estado_pau,
                        'creado_en' => $asignacion->creado_en,
                        'actualizado_en' => $asignacion->actualizado_en,
                        'eliminado_en' => $asignacion->eliminado_en,
                        'id_usuario_creador' => $asignacion->id_usuario_creador,
                        'id_usuario_actualizador' => $asignacion->id_usuario_actualizador,
                        'id_usuario_eliminador' => $asignacion->id_usuario_eliminador,
                    ]);
                }

                $mapeoAsignaciones[$asignacion->id_pau][(int) $turnoId] = $nuevoId;
            }
        }

        $inscripciones = DB::table('tbl_inscripcion')
            ->join('tbl_proceso_oferta', 'tbl_proceso_oferta.id_pof', '=', 'tbl_inscripcion.id_proceso_oferta')
            ->whereNotNull('tbl_inscripcion.id_proceso_aula')
            ->select('tbl_inscripcion.id_ins', 'tbl_inscripcion.id_proceso_aula', 'tbl_proceso_oferta.id_turno')
            ->get();

        foreach ($inscripciones as $inscripcion) {
            $nuevoProcesoAulaId = $mapeoAsignaciones[$inscripcion->id_proceso_aula][(int) $inscripcion->id_turno] ?? null;

            if ($nuevoProcesoAulaId === null) {
                continue;
            }

            DB::table('tbl_inscripcion')
                ->where('id_ins', $inscripcion->id_ins)
                ->update(['id_proceso_aula' => $nuevoProcesoAulaId]);
        }
    }

    private function tieneIndice(string $tabla, string $indice): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indices = DB::select("PRAGMA index_list('{$tabla}')");

            return collect($indices)->contains(fn (object $item): bool => ($item->name ?? null) === $indice);
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $tabla)
            ->where('index_name', $indice)
            ->exists();
    }

    private function tieneLlaveForanea(string $tabla, string $llave): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $tabla)
            ->where('constraint_name', $llave)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
