<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $accionesLegadas = DB::table('tbl_accion')
            ->select([
                'id_acc',
                'nombre_acc',
                'modulo_acc',
                'estado_acc',
                'creado_en',
                'actualizado_en',
                'eliminado_en',
                'id_usuario_creador',
                'id_usuario_actualizador',
                'id_usuario_eliminador',
            ])
            ->orderBy('id_acc')
            ->get();

        $rolesAccionesLegadas = DB::table('tbl_rol_accion')
            ->select([
                'id_rol',
                'id_accion',
                'creado_en',
                'actualizado_en',
                'eliminado_en',
                'id_usuario_creador',
                'id_usuario_actualizador',
                'id_usuario_eliminador',
            ])
            ->get();

        Schema::create('tbl_permiso', function (Blueprint $table): void {
            $table->bigIncrements('id_permiso');
            $table->string('nombre_permiso');
            $table->string('clave_permiso');
            $table->enum('estado_permiso', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('nombre_permiso', 'uk_nombre_permiso');
            $table->unique('clave_permiso', 'uk_clave_permiso');
            $table->index('estado_permiso', 'idx_permiso_estado');
        });

        $this->addAuditForeignKeys('tbl_permiso');

        $permisosLegados = $accionesLegadas
            ->map(fn (object $permiso): array => [
                'nombre_permiso' => $permiso->nombre_acc,
                'clave_permiso' => $permiso->modulo_acc,
                'estado_permiso' => $permiso->estado_acc,
                'creado_en' => $permiso->creado_en,
                'actualizado_en' => $permiso->actualizado_en,
                'eliminado_en' => $permiso->eliminado_en,
                'id_usuario_creador' => $permiso->id_usuario_creador,
                'id_usuario_actualizador' => $permiso->id_usuario_actualizador,
                'id_usuario_eliminador' => $permiso->id_usuario_eliminador,
            ])
            ->all();

        if ($permisosLegados !== []) {
            DB::table('tbl_permiso')->insert($permisosLegados);
        }

        Schema::dropIfExists('tbl_rol_accion');
        Schema::dropIfExists('tbl_accion');

        Schema::create('tbl_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_acc');
            $table->string('nombre_accion');
            $table->string('clave_accion');
            $table->enum('estado_accion', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('nombre_accion', 'uk_nombre_accion');
            $table->unique('clave_accion', 'uk_clave_accion');
            $table->index('estado_accion', 'idx_accion_estado');
        });

        DB::table('tbl_accion')->insert($this->defaultActions());

        Schema::create('tbl_permiso_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_peracc');
            $table->foreignId('id_permiso');
            $table->foreignId('id_accion');
            $table->string('clave_peracc');
            $table->enum('estado_peracc', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique(['id_permiso', 'id_accion'], 'uk_permiso_accion');
            $table->unique('clave_peracc', 'uk_clave_permiso_accion');
            $table->index('id_accion', 'idx_permiso_accion_accion');
            $table->index('estado_peracc', 'idx_permiso_accion_estado');
        });

        Schema::create('tbl_rol_permiso_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_rpa');
            $table->foreignId('id_rol');
            $table->foreignId('id_peracc');
            $this->addAuditColumns($table);
            $table->unique(['id_rol', 'id_peracc'], 'uk_rol_permiso_accion');
            $table->index('id_peracc', 'idx_rol_permiso_accion_peracc');
        });

        $this->addAuditForeignKeys('tbl_accion');
        $this->addAuditForeignKeys('tbl_permiso_accion');
        $this->addAuditForeignKeys('tbl_rol_permiso_accion');

        Schema::table('tbl_permiso_accion', function (Blueprint $table): void {
            $table->foreign('id_permiso', 'fk_permiso_accion_permiso')->references('id_permiso')->on('tbl_permiso')->restrictOnDelete();
            $table->foreign('id_accion', 'fk_permiso_accion_accion')->references('id_acc')->on('tbl_accion')->restrictOnDelete();
        });

        Schema::table('tbl_rol_permiso_accion', function (Blueprint $table): void {
            $table->foreign('id_rol', 'fk_rol_permiso_accion_rol')->references('id_rol')->on('tbl_rol')->restrictOnDelete();
            $table->foreign('id_peracc', 'fk_rol_permiso_accion_peracc')->references('id_peracc')->on('tbl_permiso_accion')->restrictOnDelete();
        });

        $this->migrarAsignacionesLegadas($accionesLegadas->all(), $rolesAccionesLegadas->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rol_permiso_accion');
        Schema::dropIfExists('tbl_permiso_accion');
        Schema::dropIfExists('tbl_accion');
        Schema::dropIfExists('tbl_permiso');

        Schema::create('tbl_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_acc');
            $table->string('codigo_acc');
            $table->string('nombre_acc');
            $table->string('modulo_acc');
            $table->text('descripcion_acc')->nullable();
            $table->enum('estado_acc', ['HABILITADO', 'DESHABILITADO'])->default('HABILITADO');
            $this->addAuditColumns($table);
            $table->unique('codigo_acc', 'uk_codigo_acc');
            $table->index(['modulo_acc', 'estado_acc'], 'idx_accion_modulo_estado');
        });

        Schema::create('tbl_rol_accion', function (Blueprint $table): void {
            $table->bigIncrements('id_rac');
            $table->foreignId('id_rol');
            $table->foreignId('id_accion');
            $this->addAuditColumns($table);
            $table->unique(['id_rol', 'id_accion'], 'uk_rol_accion');
            $table->index('id_accion', 'idx_rol_accion_accion');
        });

        $this->addAuditForeignKeys('tbl_accion');
        $this->addAuditForeignKeys('tbl_rol_accion');

        Schema::table('tbl_rol_accion', function (Blueprint $table): void {
            $table->foreign('id_rol', 'fk_rol_accion_rol')->references('id_rol')->on('tbl_rol')->restrictOnDelete();
            $table->foreign('id_accion', 'fk_rol_accion_accion')->references('id_acc')->on('tbl_accion')->restrictOnDelete();
        });
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
     * @return array<int, array<string, mixed>>
     */
    private function defaultActions(): array
    {
        return [
            [
                'nombre_accion' => 'Indice',
                'clave_accion' => 'indice',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Crear',
                'clave_accion' => 'crear',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Editar',
                'clave_accion' => 'editar',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Estado',
                'clave_accion' => 'estado',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Permisos',
                'clave_accion' => 'permisos',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Eliminar',
                'clave_accion' => 'eliminar',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Exportar PDF',
                'clave_accion' => 'exportar_pdf',
                'estado_accion' => 'HABILITADO',
            ],
            [
                'nombre_accion' => 'Exportar Excel',
                'clave_accion' => 'exportar_excel',
                'estado_accion' => 'HABILITADO',
            ],
        ];
    }

    /**
     * @param  array<int, object>  $accionesLegadas
     * @param  array<int, object>  $rolesAccionesLegadas
     */
    private function migrarAsignacionesLegadas(array $accionesLegadas, array $rolesAccionesLegadas): void
    {
        if ($accionesLegadas === []) {
            return;
        }

        $indiceId = DB::table('tbl_accion')
            ->where('clave_accion', 'indice')
            ->value('id_acc');

        if (! is_numeric($indiceId)) {
            return;
        }

        $indiceId = (int) $indiceId;

        $permisosPorClave = DB::table('tbl_permiso')
            ->select(['id_permiso', 'clave_permiso'])
            ->get()
            ->keyBy('clave_permiso');

        $permisoAccionesPorAccionLegada = collect($accionesLegadas)
            ->mapWithKeys(function (object $accionLegada) use ($permisosPorClave, $indiceId): array {
                $permiso = $permisosPorClave->get($accionLegada->modulo_acc);

                if ($permiso === null) {
                    return [];
                }

                $permisoAccionId = DB::table('tbl_permiso_accion')->insertGetId([
                    'id_permiso' => $permiso->id_permiso,
                    'id_accion' => $indiceId,
                    'clave_peracc' => "{$accionLegada->modulo_acc}_indice",
                    'estado_peracc' => $accionLegada->estado_acc,
                    'creado_en' => $accionLegada->creado_en,
                    'actualizado_en' => $accionLegada->actualizado_en,
                    'eliminado_en' => $accionLegada->eliminado_en,
                    'id_usuario_creador' => $accionLegada->id_usuario_creador,
                    'id_usuario_actualizador' => $accionLegada->id_usuario_actualizador,
                    'id_usuario_eliminador' => $accionLegada->id_usuario_eliminador,
                ]);

                return [$accionLegada->id_acc => $permisoAccionId];
            });

        $rolesPermisoAccion = collect($rolesAccionesLegadas)
            ->map(function (object $rolAccionLegado) use ($permisoAccionesPorAccionLegada): ?array {
                $idPermisoAccion = $permisoAccionesPorAccionLegada->get($rolAccionLegado->id_accion);

                if (! is_numeric($idPermisoAccion)) {
                    return null;
                }

                return [
                    'id_rol' => $rolAccionLegado->id_rol,
                    'id_peracc' => (int) $idPermisoAccion,
                    'creado_en' => $rolAccionLegado->creado_en,
                    'actualizado_en' => $rolAccionLegado->actualizado_en,
                    'eliminado_en' => $rolAccionLegado->eliminado_en,
                    'id_usuario_creador' => $rolAccionLegado->id_usuario_creador,
                    'id_usuario_actualizador' => $rolAccionLegado->id_usuario_actualizador,
                    'id_usuario_eliminador' => $rolAccionLegado->id_usuario_eliminador,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($rolesPermisoAccion !== []) {
            DB::table('tbl_rol_permiso_accion')->insert($rolesPermisoAccion);
        }
    }
};
