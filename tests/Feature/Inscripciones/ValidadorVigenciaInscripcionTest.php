<?php

use App\Enums\EstadoProceso;
use App\Models\ConvenioProceso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Support\ValidadorVigenciaInscripcion;
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

beforeEach(function () {
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

    $this->proceso = Proceso::query()->where('codigo_pro', 'ordinario_2026_1')->firstOrFail();
    $this->procesoOferta = ProcesoOferta::query()
        ->where('id_proceso', $this->proceso->id_pro)
        ->where('estado_pof', EstadoProceso::Habilitado->value)
        ->firstOrFail();
    $this->validador = app(ValidadorVigenciaInscripcion::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('an enabled offer of the active process is accepted', function () {
    $oferta = $this->validador->ofertaVigente($this->procesoOferta->id_pof, $this->proceso);

    expect($oferta->id_pof)->toBe($this->procesoOferta->id_pof)
        ->and($oferta->relationLoaded('ofertaBase'))->toBeTrue();
});

test('a disabled offer is rejected', function () {
    $this->procesoOferta->update(['estado_pof' => EstadoProceso::Deshabilitado->value]);

    $this->validador->ofertaVigente($this->procesoOferta->id_pof, $this->proceso);
})->throws(RuntimeException::class, 'La oferta seleccionada ya no se encuentra disponible para el proceso activo.');

test('without an agreement there is nothing to validate', function () {
    expect($this->validador->convenioVigente(null, $this->proceso))->toBeNull();
});

test('an agreement is accepted inside its validity window', function () {
    Carbon::setTestNow('2026-04-15 10:00:00');

    $convenioProceso = ConvenioProceso::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();

    expect($this->validador->convenioVigente($convenioProceso->id_cpr, $this->proceso)?->id_cpr)
        ->toBe($convenioProceso->id_cpr);
});

test('an expired agreement is rejected with its end date', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');

    $convenioProceso = ConvenioProceso::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();

    $this->validador->convenioVigente($convenioProceso->id_cpr, $this->proceso);
})->throws(
    RuntimeException::class,
    'El convenio asociado a la inscripción ya no se encuentra vigente para el proceso activo. Su vigencia terminó el 30/04/2026.',
);

test('a payment rule of the inscription process is accepted inside its validity window', function () {
    Carbon::setTestNow('2026-04-15 10:00:00');

    $reglaPago = ReglaPago::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();
    $inscripcion = (new Inscripcion)->forceFill([
        'id_proceso_oferta' => $this->procesoOferta->id_pof,
        'id_convenio_proceso' => null,
    ]);

    expect($this->validador->reglaPagoVigente($reglaPago->id_rpa, $inscripcion)->id_rpa)->toBe($reglaPago->id_rpa);
});

test('an expired payment rule is rejected with its end date', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');

    $reglaPago = ReglaPago::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();
    $inscripcion = (new Inscripcion)->forceFill([
        'id_proceso_oferta' => $this->procesoOferta->id_pof,
        'id_convenio_proceso' => null,
    ]);

    $this->validador->reglaPagoVigente($reglaPago->id_rpa, $inscripcion);
})->throws(
    RuntimeException::class,
    'La regla de pago seleccionada ya no se encuentra vigente para esta inscripción. Su vigencia terminó el 30/04/2026.',
);

test('a payment rule cannot be validated without an inscription process', function () {
    $reglaPago = ReglaPago::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();

    $this->validador->reglaPagoVigente($reglaPago->id_rpa, new Inscripcion);
})->throws(RuntimeException::class, 'La inscripción ya no tiene un proceso válido para continuar con el pago.');
