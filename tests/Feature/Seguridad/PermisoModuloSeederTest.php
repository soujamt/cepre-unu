<?php

use Database\Seeders\PermisoModuloSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('permission module seeder creates base permisos and acciones for active modules', function () {
    Artisan::call('db:seed', ['--class' => PermisoModuloSeeder::class, '--no-interaction' => true]);

    expect(DB::table('tbl_permiso')->where('clave_permiso', 'usuario')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'rol')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'permiso')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'tipo_expediente')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'semestre')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'proceso')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'oferta')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'convenio')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'regla_pago')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'requisito_expediente')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'pago')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso')->where('clave_permiso', 'inscripcion')->exists())->toBeTrue();

    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'usuario_indice')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'rol_permisos')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'permiso_estado')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'tipo_expediente_eliminar')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'semestre_indice')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'proceso_editar')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'oferta_estado')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'convenio_crear')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'regla_pago_eliminar')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'requisito_expediente_indice')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'pago_crear')->exists())->toBeTrue();
    expect(DB::table('tbl_permiso_accion')->where('clave_peracc', 'inscripcion_indice')->exists())->toBeTrue();
});
