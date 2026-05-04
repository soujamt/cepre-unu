<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AulaProcesoPermisoSeeder extends Seeder
{
    public function run(): void
    {
        $acciones = DB::table('tbl_accion')
            ->pluck('id_acc', 'clave_accion');

        $modulo = [
            'clave_permiso' => 'aula_proceso',
            'nombre_permiso' => 'AULAS POR PROCESO',
            'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
        ];

        DB::table('tbl_permiso')->updateOrInsert(
            ['clave_permiso' => $modulo['clave_permiso']],
            [
                'nombre_permiso' => $modulo['nombre_permiso'],
                'clave_permiso' => $modulo['clave_permiso'],
                'estado_permiso' => 'HABILITADO',
                'eliminado_en' => null,
            ],
        );

        $permisos = DB::table('tbl_permiso')
            ->pluck('id_permiso', 'clave_permiso');

        $permisoId = $permisos[$modulo['clave_permiso']] ?? null;

        if (is_numeric($permisoId)) {
            foreach ($modulo['acciones'] as $claveAccion) {
                $accionId = $acciones[$claveAccion] ?? null;

                if (is_numeric($accionId)) {
                    DB::table('tbl_permiso_accion')->updateOrInsert(
                        ['clave_peracc' => "{$modulo['clave_permiso']}_{$claveAccion}"],
                        [
                            'id_permiso' => (int) $permisoId,
                            'id_accion' => (int) $accionId,
                            'clave_peracc' => "{$modulo['clave_permiso']}_{$claveAccion}",
                            'estado_peracc' => 'HABILITADO',
                            'eliminado_en' => null,
                        ],
                    );
                }
            }
        }
    }
}
