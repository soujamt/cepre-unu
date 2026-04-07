<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_modalidad_lugar_inscripcion')) {
            Schema::create('tbl_modalidad_lugar_inscripcion', function (Blueprint $table): void {
                $table->bigIncrements('id_mli');
                $table->unsignedTinyInteger('id_modalidad_admision');
                $table->unsignedInteger('id_lugar_inscripcion');
                $table->enum('estado_mli', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
                $this->addAuditColumns($table);
                $table->unique(['id_modalidad_admision', 'id_lugar_inscripcion'], 'uk_modalidad_lugar_inscripcion');
                $table->index(['id_modalidad_admision', 'estado_mli'], 'idx_mli_modalidad_estado');
                $table->index(['id_lugar_inscripcion', 'estado_mli'], 'idx_mli_lugar_estado');
            });

            $this->addAuditForeignKeys('tbl_modalidad_lugar_inscripcion');

            Schema::table('tbl_modalidad_lugar_inscripcion', function (Blueprint $table): void {
                $table->foreign('id_modalidad_admision', 'fk_mli_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
                $table->foreign('id_lugar_inscripcion', 'fk_mli_lugar')->references('id_lin')->on('tbl_lugar_inscripcion')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('tbl_oferta_base', 'id_modalidad_lugar_inscripcion')) {
            Schema::table('tbl_oferta_base', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_modalidad_lugar_inscripcion')->nullable()->after('codigo_externo_ofb');
            });
        }

        if (! Schema::hasColumn('tbl_oferta_base', 'id_modalidad_admision') || ! Schema::hasColumn('tbl_oferta_base', 'id_lugar_inscripcion')) {
            return;
        }

        $parejas = DB::table('tbl_oferta_base')
            ->select('id_modalidad_admision', 'id_lugar_inscripcion')
            ->distinct()
            ->get();

        foreach ($parejas as $pareja) {
            DB::table('tbl_modalidad_lugar_inscripcion')->updateOrInsert(
                [
                    'id_modalidad_admision' => $pareja->id_modalidad_admision,
                    'id_lugar_inscripcion' => $pareja->id_lugar_inscripcion,
                ],
                [
                    'estado_mli' => 'HABILITADO',
                ],
            );
        }

        $mapaModalidadLugar = DB::table('tbl_modalidad_lugar_inscripcion')
            ->get()
            ->keyBy(fn (object $record): string => "{$record->id_modalidad_admision}|{$record->id_lugar_inscripcion}");

        $ofertas = DB::table('tbl_oferta_base')
            ->select('id_ofb', 'id_modalidad_admision', 'id_lugar_inscripcion')
            ->get();

        foreach ($ofertas as $oferta) {
            $idModalidadLugar = $mapaModalidadLugar["{$oferta->id_modalidad_admision}|{$oferta->id_lugar_inscripcion}"]->id_mli ?? null;

            DB::table('tbl_oferta_base')
                ->where('id_ofb', $oferta->id_ofb)
                ->update([
                    'id_modalidad_lugar_inscripcion' => $idModalidadLugar,
                ]);
        }

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $this->dropForeignKey($table, 'fk_oferta_base_modalidad', ['id_modalidad_admision']);
            $this->dropForeignKey($table, 'fk_oferta_base_lugar', ['id_lugar_inscripcion']);
            $table->dropUnique('uk_oferta_base');
            $table->dropIndex('idx_oferta_base_lugar_estado');
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->dropColumn(['id_modalidad_admision', 'id_lugar_inscripcion']);
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->unique(['id_modalidad_lugar_inscripcion', 'id_sede_carrera'], 'uk_oferta_base');
            $table->index(['id_modalidad_lugar_inscripcion', 'estado_ofb'], 'idx_oferta_base_mli_estado');
            $table->foreign('id_modalidad_lugar_inscripcion', 'fk_oferta_base_mli')
                ->references('id_mli')
                ->on('tbl_modalidad_lugar_inscripcion')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id_modalidad_admision')->nullable()->after('codigo_externo_ofb');
            $table->unsignedInteger('id_lugar_inscripcion')->nullable()->after('id_modalidad_admision');
        });

        $mapaModalidadLugar = DB::table('tbl_modalidad_lugar_inscripcion')
            ->select('id_mli', 'id_modalidad_admision', 'id_lugar_inscripcion')
            ->get()
            ->keyBy('id_mli');

        $ofertas = DB::table('tbl_oferta_base')
            ->select('id_ofb', 'id_modalidad_lugar_inscripcion')
            ->get();

        foreach ($ofertas as $oferta) {
            $modalidadLugar = $mapaModalidadLugar[$oferta->id_modalidad_lugar_inscripcion] ?? null;

            DB::table('tbl_oferta_base')
                ->where('id_ofb', $oferta->id_ofb)
                ->update([
                    'id_modalidad_admision' => $modalidadLugar?->id_modalidad_admision,
                    'id_lugar_inscripcion' => $modalidadLugar?->id_lugar_inscripcion,
                ]);
        }

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $this->dropForeignKey($table, 'fk_oferta_base_mli', ['id_modalidad_lugar_inscripcion']);
            $table->dropUnique('uk_oferta_base');
            $table->dropIndex('idx_oferta_base_mli_estado');
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->unique(['id_modalidad_admision', 'id_lugar_inscripcion', 'id_sede_carrera'], 'uk_oferta_base');
            $table->index(['id_lugar_inscripcion', 'estado_ofb'], 'idx_oferta_base_lugar_estado');
            $table->foreign('id_modalidad_admision', 'fk_oferta_base_modalidad')->references('id_mad')->on('tbl_modalidad_admision')->restrictOnDelete();
            $table->foreign('id_lugar_inscripcion', 'fk_oferta_base_lugar')->references('id_lin')->on('tbl_lugar_inscripcion')->restrictOnDelete();
        });

        Schema::table('tbl_oferta_base', function (Blueprint $table): void {
            $table->dropColumn('id_modalidad_lugar_inscripcion');
        });

        Schema::dropIfExists('tbl_modalidad_lugar_inscripcion');
    }

    private function addAuditColumns(Blueprint $table): void
    {
        $table->timestamp('creado_en')->useCurrent();
        $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        $table->timestamp('eliminado_en')->nullable();
        $table->unsignedBigInteger('id_usuario_creador')->nullable();
        $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
        $table->unsignedBigInteger('id_usuario_eliminador')->nullable();
    }

    private function addAuditForeignKeys(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            $table->foreign('id_usuario_creador', "{$tableName}_fk_usuario_creador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_actualizador', "{$tableName}_fk_usuario_actualizador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
            $table->foreign('id_usuario_eliminador', "{$tableName}_fk_usuario_eliminador")->references('id_usu')->on('tbl_usuario')->restrictOnDelete();
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropForeignKey(Blueprint $table, string $indexName, array $columns): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $table->dropForeign($columns);

            return;
        }

        $table->dropForeign($indexName);
    }
};
