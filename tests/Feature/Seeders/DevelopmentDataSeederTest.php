<?php

use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('loads demo data for the user manual with 50 active inscriptions from Coronel Portillo', function (): void {
    Artisan::call('db:seed', ['--class' => DevelopmentDataSeeder::class, '--no-interaction' => true]);

    expect(DB::table('tbl_usuario')->where('usuario_usu', 'superadmin')->exists())->toBeTrue();
    expect(DB::table('tbl_usuario')->where('usuario_usu', 'gestor.cepre')->exists())->toBeTrue();
    expect(DB::table('tbl_rol')->where('nombre_rol', 'GESTOR DE INSCRIPCIONES CEPRE')->exists())->toBeTrue();

    $inscripciones = DB::table('tbl_inscripcion')
        ->join('tbl_persona', 'tbl_persona.id_per', '=', 'tbl_inscripcion.id_persona')
        ->join('tbl_modalidad_admision', 'tbl_modalidad_admision.id_mad', '=', 'tbl_inscripcion.id_modalidad_admision')
        ->join('tbl_sede', 'tbl_sede.id_sed', '=', 'tbl_inscripcion.id_sede')
        ->join('tbl_turno', 'tbl_turno.id_tur', '=', 'tbl_inscripcion.id_turno')
        ->join('tbl_ubigeo', 'tbl_ubigeo.id_ubi', '=', 'tbl_persona.id_ubigeo_domicilio')
        ->where('tbl_inscripcion.codigo_ins', 'like', 'MANUAL-INS-%')
        ->select([
            'tbl_inscripcion.estado_ins',
            'tbl_modalidad_admision.nombre_mad',
            'tbl_sede.nombre_sed',
            'tbl_turno.nombre_tur',
            'tbl_ubigeo.nombre_departamento_ubi',
            'tbl_ubigeo.nombre_provincia_ubi',
        ])
        ->get();

    expect($inscripciones)->toHaveCount(50);
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->estado_ins === 'ACTIVO'))->toBeTrue();
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->nombre_mad === 'ORDINARIO'))->toBeTrue();
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->nombre_sed === 'PUCALLPA - CALLERÍA'))->toBeTrue();
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->nombre_tur === 'MAÑANA'))->toBeTrue();
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->nombre_departamento_ubi === 'UCAYALI'))->toBeTrue();
    expect($inscripciones->every(fn (object $inscripcion): bool => $inscripcion->nombre_provincia_ubi === 'CORONEL PORTILLO'))->toBeTrue();

    expect(DB::table('tbl_inscripcion_obligacion_pago')->count())->toBe(50);
    expect(DB::table('tbl_inscripcion_pago')->count())->toBe(50);
});
