<?php

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Support\ProcesoInscripcionResolver;
use Carbon\Carbon;
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

uses(RefreshDatabase::class);

function sembrarConfiguracionAcademicaInicial(): void
{
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
}

test('resolver returns the active process when the inscription window is open', function () {
    sembrarConfiguracionAcademicaInicial();

    Carbon::setTestNow('2026-04-15 10:00:00');

    $contexto = app(ProcesoInscripcionResolver::class)->resolver();

    expect($contexto['disponible'])->toBeTrue()
        ->and($contexto['mensaje'])->toBe('Proceso disponible para continuar con la postulacion.')
        ->and($contexto['proceso'])->not->toBeNull()
        ->and($contexto['proceso']?->codigo_pro)->toBe('2026_1');

    Carbon::setTestNow();
});

test('resolver indicates when there is no active process for the current date', function () {
    sembrarConfiguracionAcademicaInicial();

    Carbon::setTestNow('2026-05-02 09:00:00');

    $contexto = app(ProcesoInscripcionResolver::class)->resolver();

    expect($contexto['disponible'])->toBeFalse()
        ->and($contexto['mensaje'])->toBe('El último proceso de inscripción cerró el 30/04/2026 a las 11:59 pm.')
        ->and($contexto['proceso'])->toBeNull();

    expect(Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail()->estado_pro)
        ->toBe(EstadoProceso::Cerrado);

    Carbon::setTestNow();
});

test('resolver indicates when the active process has no enabled offers', function () {
    sembrarConfiguracionAcademicaInicial();

    Carbon::setTestNow('2026-04-15 09:00:00');

    $proceso = Proceso::query()
        ->where('codigo_pro', '2026_1')
        ->firstOrFail();

    ProcesoOferta::query()
        ->where('id_proceso', $proceso->id_pro)
        ->update(['estado_pof' => EstadoProceso::Deshabilitado->value]);

    $contexto = app(ProcesoInscripcionResolver::class)->resolver();

    expect($contexto['disponible'])->toBeFalse()
        ->and($contexto['mensaje'])->toBe('El proceso activo no tiene ofertas habilitadas para inscripcion.')
        ->and($contexto['proceso'])->not->toBeNull()
        ->and($contexto['proceso']?->id_pro)->toBe($proceso->id_pro);

    Carbon::setTestNow();
});

test('resolver opens and closes the process at the exact configured times', function () {
    sembrarConfiguracionAcademicaInicial();

    $proceso = Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail();
    $proceso->update([
        'fecha_inicio_pro' => '2026-04-15 08:00:00',
        'fecha_fin_pro' => '2026-04-20 18:00:00',
    ]);

    $resolver = app(ProcesoInscripcionResolver::class);

    Carbon::setTestNow('2026-04-15 07:59:00');
    $antesDeAbrir = $resolver->resolver();

    expect($antesDeAbrir['disponible'])->toBeFalse()
        ->and($antesDeAbrir['mensaje'])->toBe('El próximo proceso de inscripción inicia el 15/04/2026 a las 08:00 am.');

    Carbon::setTestNow('2026-04-15 08:00:00');
    expect($resolver->resolver()['disponible'])->toBeTrue();

    Carbon::setTestNow('2026-04-20 18:00:00');
    expect($resolver->resolver()['disponible'])->toBeTrue();

    Carbon::setTestNow('2026-04-20 18:01:00');
    $despuesDeCerrar = $resolver->resolver();

    expect($despuesDeCerrar['disponible'])->toBeFalse()
        ->and($despuesDeCerrar['mensaje'])->toBe('El último proceso de inscripción cerró el 20/04/2026 a las 06:00 pm.')
        ->and($proceso->fresh()->estado_pro)->toBe(EstadoProceso::Cerrado);

    Carbon::setTestNow();
});
