<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<string, array{nombre: string, acciones: list<string>}>
     */
    private array $permisos = [
        'foto' => ['nombre' => 'FOTOS', 'acciones' => ['indice', 'crear', 'eliminar', 'exportar_excel']],
        'parametro' => ['nombre' => 'CONFIGURACIÓN DEL CARNET', 'acciones' => ['indice', 'editar']],
    ];

    /**
     * Fotos de los postulantes por DNI, código de verificación del carnet y
     * parámetros generales (firma y datos del director).
     */
    public function up(): void
    {
        Schema::create('tbl_foto_postulante', function (Blueprint $table): void {
            $table->bigIncrements('id_fot');
            $table->string('numero_documento_fot', 12);
            $table->string('archivo_fot');
            $this->addAuditColumns($table);
            $table->unique('numero_documento_fot', 'uk_foto_postulante_documento');
        });

        Schema::create('tbl_parametro', function (Blueprint $table): void {
            $table->increments('id_par');
            $table->string('clave_par')->unique('uk_parametro_clave');
            $table->text('valor_par')->nullable();
            $this->addAuditColumns($table);
        });

        $this->addAuditForeignKeys('tbl_foto_postulante');
        $this->addAuditForeignKeys('tbl_parametro');

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->string('token_verificacion_ins', 40)->nullable()->after('codigo_ins');
        });

        DB::table('tbl_inscripcion')->whereNull('token_verificacion_ins')->orderBy('id_ins')->pluck('id_ins')
            ->each(fn (int $id) => DB::table('tbl_inscripcion')->where('id_ins', $id)->update(['token_verificacion_ins' => Str::random(40)]));

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->unique('token_verificacion_ins', 'uk_inscripcion_token_verificacion');
        });

        foreach ([
            'carnet_director_nombre' => '',
            'carnet_director_cargo' => 'DIRECTOR GENERAL CEPRE-UNU',
            'carnet_nota' => 'Este carnet es válido exclusivamente para el ingreso al campus universitario.',
        ] as $clave => $valor) {
            DB::table('tbl_parametro')->insertOrIgnore(['clave_par' => $clave, 'valor_par' => $valor]);
        }

        $this->registrarPermisos();
    }

    public function down(): void
    {
        $idsPermiso = DB::table('tbl_permiso')->whereIn('clave_permiso', array_keys($this->permisos))->pluck('id_permiso');
        $idsPermisoAccion = DB::table('tbl_permiso_accion')->whereIn('id_permiso', $idsPermiso)->pluck('id_peracc');
        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso')->whereIn('id_permiso', $idsPermiso)->delete();

        Schema::table('tbl_inscripcion', function (Blueprint $table): void {
            $table->dropUnique('uk_inscripcion_token_verificacion');
            $table->dropColumn('token_verificacion_ins');
        });

        Schema::dropIfExists('tbl_parametro');
        Schema::dropIfExists('tbl_foto_postulante');
    }

    private function registrarPermisos(): void
    {
        $idsAccion = DB::table('tbl_accion')->pluck('id_acc', 'clave_accion');

        foreach ($this->permisos as $clave => $permiso) {
            DB::table('tbl_permiso')->updateOrInsert(
                ['clave_permiso' => $clave],
                ['nombre_permiso' => $permiso['nombre'], 'estado_permiso' => 'HABILITADO', 'eliminado_en' => null],
            );

            $idPermiso = DB::table('tbl_permiso')->where('clave_permiso', $clave)->value('id_permiso');

            foreach ($permiso['acciones'] as $accion) {
                if (! isset($idsAccion[$accion])) {
                    continue;
                }

                DB::table('tbl_permiso_accion')->updateOrInsert(
                    ['clave_peracc' => "{$clave}_{$accion}"],
                    ['id_permiso' => $idPermiso, 'id_accion' => $idsAccion[$accion], 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
                );
            }
        }
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
};
