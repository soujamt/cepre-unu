<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $modulos = [
        'turno' => 'TURNOS',
        'concepto_pago' => 'CONCEPTOS DE PAGO',
    ];

    /**
     * @var list<string>
     */
    private array $acciones = ['indice', 'crear', 'editar', 'estado', 'eliminar'];

    /**
     * Registra los permisos de los mantenedores de turnos y conceptos de pago.
     */
    public function up(): void
    {
        $idsAccion = DB::table('tbl_accion')
            ->whereIn('clave_accion', $this->acciones)
            ->pluck('id_acc', 'clave_accion');

        foreach ($this->modulos as $clavePermiso => $nombrePermiso) {
            DB::table('tbl_permiso')->updateOrInsert(
                ['clave_permiso' => $clavePermiso],
                [
                    'nombre_permiso' => $nombrePermiso,
                    'estado_permiso' => 'HABILITADO',
                    'eliminado_en' => null,
                ],
            );

            $idPermiso = DB::table('tbl_permiso')->where('clave_permiso', $clavePermiso)->value('id_permiso');

            foreach ($this->acciones as $claveAccion) {
                $idAccion = $idsAccion[$claveAccion] ?? null;

                if ($idPermiso === null || $idAccion === null) {
                    continue;
                }

                DB::table('tbl_permiso_accion')->updateOrInsert(
                    ['clave_peracc' => "{$clavePermiso}_{$claveAccion}"],
                    [
                        'id_permiso' => $idPermiso,
                        'id_accion' => $idAccion,
                        'estado_peracc' => 'HABILITADO',
                        'eliminado_en' => null,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        $idsPermiso = DB::table('tbl_permiso')
            ->whereIn('clave_permiso', array_keys($this->modulos))
            ->pluck('id_permiso');

        $idsPermisoAccion = DB::table('tbl_permiso_accion')
            ->whereIn('id_permiso', $idsPermiso)
            ->pluck('id_peracc');

        DB::table('tbl_rol_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso_accion')->whereIn('id_peracc', $idsPermisoAccion)->delete();
        DB::table('tbl_permiso')->whereIn('id_permiso', $idsPermiso)->delete();
    }
};
