<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapeos = [
            ['plural' => 'permisos', 'singular' => 'permiso', 'nombre' => 'PERMISO'],
            ['plural' => 'roles', 'singular' => 'rol', 'nombre' => 'ROL'],
            ['plural' => 'usuarios', 'singular' => 'usuario', 'nombre' => 'USUARIO'],
        ];

        foreach ($mapeos as $mapeo) {
            DB::table('tbl_permiso')
                ->where('clave_permiso', $mapeo['plural'])
                ->update([
                    'clave_permiso' => $mapeo['singular'],
                    'nombre_permiso' => $mapeo['nombre'],
                ]);

            $permiso = DB::table('tbl_permiso')
                ->where('clave_permiso', $mapeo['singular'])
                ->first(['id_permiso']);

            if (! $permiso) {
                continue;
            }

            $permisoAcciones = DB::table('tbl_permiso_accion')
                ->where('id_permiso', $permiso->id_permiso)
                ->get(['id_peracc', 'clave_peracc']);

            foreach ($permisoAcciones as $permisoAccion) {
                $claveActualizada = preg_replace(
                    '/^'.preg_quote($mapeo['plural'], '/').'_(.+)$/',
                    $mapeo['singular'].'_$1',
                    $permisoAccion->clave_peracc,
                );

                if (! is_string($claveActualizada) || $claveActualizada === $permisoAccion->clave_peracc) {
                    continue;
                }

                DB::table('tbl_permiso_accion')
                    ->where('id_peracc', $permisoAccion->id_peracc)
                    ->update([
                        'clave_peracc' => $claveActualizada,
                    ]);
            }
        }
    }

    public function down(): void
    {
        $mapeos = [
            ['plural' => 'permisos', 'singular' => 'permiso', 'nombre' => 'PERMISOS'],
            ['plural' => 'roles', 'singular' => 'rol', 'nombre' => 'ROLES'],
            ['plural' => 'usuarios', 'singular' => 'usuario', 'nombre' => 'USUARIOS'],
        ];

        foreach ($mapeos as $mapeo) {
            DB::table('tbl_permiso')
                ->where('clave_permiso', $mapeo['singular'])
                ->update([
                    'clave_permiso' => $mapeo['plural'],
                    'nombre_permiso' => $mapeo['nombre'],
                ]);

            $permiso = DB::table('tbl_permiso')
                ->where('clave_permiso', $mapeo['plural'])
                ->first(['id_permiso']);

            if (! $permiso) {
                continue;
            }

            $permisoAcciones = DB::table('tbl_permiso_accion')
                ->where('id_permiso', $permiso->id_permiso)
                ->get(['id_peracc', 'clave_peracc']);

            foreach ($permisoAcciones as $permisoAccion) {
                $claveActualizada = preg_replace(
                    '/^'.preg_quote($mapeo['singular'], '/').'_(.+)$/',
                    $mapeo['plural'].'_$1',
                    $permisoAccion->clave_peracc,
                );

                if (! is_string($claveActualizada) || $claveActualizada === $permisoAccion->clave_peracc) {
                    continue;
                }

                DB::table('tbl_permiso_accion')
                    ->where('id_peracc', $permisoAccion->id_peracc)
                    ->update([
                        'clave_peracc' => $claveActualizada,
                    ]);
            }
        }
    }
};
