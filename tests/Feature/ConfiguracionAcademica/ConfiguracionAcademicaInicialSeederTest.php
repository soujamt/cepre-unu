<?php

use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('academic initial seeder creates a usable base configuration', function () {
    foreach ([
        TurnoSeeder::class,
        ConceptoPagoSeeder::class,
        UbigeoSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        ModalidadAdmisionSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--no-interaction' => true]);
    }

    $proceso = DB::table('tbl_proceso')
        ->where('codigo_pro', '2026_1')
        ->first();

    expect($proceso)->not->toBeNull();
    expect($proceso->nombre_pro)->toBe('2026-1');
    expect((string) $proceso->fecha_inicio_pro)->toStartWith('2026-04-01');
    expect((string) $proceso->fecha_fin_pro)->toStartWith('2026-04-30');

    $procesoId = $proceso->id_pro;

    expect(DB::table('tbl_proceso_modalidad')
        ->where('id_proceso', $procesoId)
        ->where('estado_pmo', 'HABILITADO')
        ->count())->toBe(2);

    expect(DB::table('tbl_proceso_carrera')
        ->where('id_proceso', $procesoId)
        ->where('estado_pca', 'HABILITADO')
        ->count())->toBe(DB::table('tbl_carrera')->where('estado_car', 'HABILITADO')->count());

    expect(DB::table('tbl_regla_pago')
        ->where('id_proceso', $procesoId)
        ->where('monto_total_rpa', 500)
        ->where('permite_cuotas_rpa', false)
        ->where('numero_maximo_cuotas_rpa', 1)
        ->exists())->toBeTrue();

    expect(DB::table('tbl_regla_pago')
        ->where('id_proceso', $procesoId)
        ->where('monto_total_rpa', 550)
        ->where('permite_cuotas_rpa', true)
        ->where('monto_minimo_primera_cuota_rpa', 275)
        ->where('numero_maximo_cuotas_rpa', 2)
        ->exists())->toBeTrue();

    expect(DB::table('tbl_regla_pago')->where('id_proceso', $procesoId)->count())->toBe(5);

    expect(DB::table('tbl_regla_pago')
        ->where('id_proceso', $procesoId)
        ->where('monto_total_rpa', 0)
        ->where('documento_requerido_rpa', 'N° DE CONSTANCIA')
        ->exists())->toBeTrue();
});
