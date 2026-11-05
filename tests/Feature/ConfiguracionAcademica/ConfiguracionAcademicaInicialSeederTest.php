<?php

use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\LugarInscripcionSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\ModalidadLugarInscripcionSeeder;
use Database\Seeders\OfertaBaseSeeder;
use Database\Seeders\SedeCarreraSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoExpedienteSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('academic initial seeder creates a usable base configuration', function () {
    foreach ([
        TurnoSeeder::class,
        TipoExpedienteSeeder::class,
        ConceptoPagoSeeder::class,
        UbigeoSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        LugarInscripcionSeeder::class,
        ModalidadAdmisionSeeder::class,
        ModalidadLugarInscripcionSeeder::class,
        SedeCarreraSeeder::class,
        OfertaBaseSeeder::class,
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

    $ofertasEsperadas = DB::table('tbl_oferta_base as ofb')
        ->join('tbl_modalidad_lugar_inscripcion as mli', 'mli.id_mli', '=', 'ofb.id_modalidad_lugar_inscripcion')
        ->where('mli.id_modalidad_admision', 2)
        ->where('mli.id_lugar_inscripcion', 603)
        ->count();

    expect($ofertasEsperadas)->toBe(23);

    expect(DB::table('tbl_proceso_oferta')
        ->where('id_proceso', $procesoId)
        ->where('id_turno', 1)
        ->count())->toBe($ofertasEsperadas);

    expect(DB::table('tbl_convenio')
        ->where('codigo_con', 'HIJO_TRABAJADOR')
        ->where('monto_descuento_con', 200)
        ->exists())->toBeTrue();

    expect(DB::table('tbl_convenio')
        ->where('codigo_con', 'MUNICIPALIDAD')
        ->where('tipo_beneficio_con', 'EXONERACION_TOTAL')
        ->exists())->toBeTrue();

    expect(DB::table('tbl_convenio_proceso')
        ->where('id_proceso', $procesoId)
        ->count())->toBe(2);

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
        ->where('numero_maximo_cuotas_rpa', 3)
        ->exists())->toBeTrue();

    expect(DB::table('tbl_requisito_expediente')
        ->where('id_proceso', $procesoId)
        ->count())->toBe(3);
});
