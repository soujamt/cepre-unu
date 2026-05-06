<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoModuloSeeder extends Seeder
{
    public function run(): void
    {
        $acciones = DB::table('tbl_accion')
            ->pluck('id_acc', 'clave_accion');

        foreach ($this->modulos() as $modulo) {
            DB::table('tbl_permiso')->updateOrInsert(
                ['clave_permiso' => $modulo['clave_permiso']],
                [
                    'nombre_permiso' => $modulo['nombre_permiso'],
                    'clave_permiso' => $modulo['clave_permiso'],
                    'estado_permiso' => 'HABILITADO',
                    'eliminado_en' => null,
                ],
            );
        }

        $permisos = DB::table('tbl_permiso')
            ->pluck('id_permiso', 'clave_permiso');

        foreach ($this->modulos() as $modulo) {
            $permisoId = $permisos[$modulo['clave_permiso']] ?? null;

            if (! is_numeric($permisoId)) {
                continue;
            }

            foreach ($modulo['acciones'] as $claveAccion) {
                $accionId = $acciones[$claveAccion] ?? null;

                if (! is_numeric($accionId)) {
                    continue;
                }

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

    /**
     * @return list<array{clave_permiso:string,nombre_permiso:string,acciones:list<string>}>
     */
    private function modulos(): array
    {
        return [
            [
                'clave_permiso' => 'usuario',
                'nombre_permiso' => 'USUARIOS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'rol',
                'nombre_permiso' => 'ROLES',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'permisos', 'eliminar'],
            ],
            [
                'clave_permiso' => 'permiso',
                'nombre_permiso' => 'PERMISOS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'tipo_expediente',
                'nombre_permiso' => 'TIPOS DE EXPEDIENTE',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'aula',
                'nombre_permiso' => 'AULAS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'semestre',
                'nombre_permiso' => 'SEMESTRES',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'proceso',
                'nombre_permiso' => 'PROCESOS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'oferta',
                'nombre_permiso' => 'OFERTAS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'aula_proceso',
                'nombre_permiso' => 'AULAS POR PROCESO',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'convenio',
                'nombre_permiso' => 'CONVENIOS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'regla_pago',
                'nombre_permiso' => 'REGLAS DE PAGO',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'requisito_expediente',
                'nombre_permiso' => 'REQUISITOS DE EXPEDIENTE',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'pago',
                'nombre_permiso' => 'PAGOS',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar'],
            ],
            [
                'clave_permiso' => 'inscripcion',
                'nombre_permiso' => 'INSCRIPCIONES',
                'acciones' => ['indice', 'crear', 'editar', 'estado', 'eliminar', 'exportar_excel'],
            ],
            [
                'clave_permiso' => 'reporte_inscripcion',
                'nombre_permiso' => 'REPORTES DE INSCRIPCIONES',
                'acciones' => ['indice', 'exportar_excel'],
            ],
        ];
    }
}
