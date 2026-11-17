<?php

use App\Enums\EstadoCatalogo;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\ReglaPago;
use App\Support\ValidadorVigenciaInscripcion;
use Carbon\Carbon;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
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

    $this->proceso = Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail();
    $this->procesoCarrera = ProcesoCarrera::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();
    $this->modalidad = ModalidadAdmision::query()->where('codigo_mad', 'ORD')->firstOrFail();
    $this->validador = app(ValidadorVigenciaInscripcion::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('an enabled carrera and modalidad of the active process are accepted', function () {
    $this->validador->postulacionVigente(
        $this->procesoCarrera->id_sede,
        $this->procesoCarrera->id_carrera,
        $this->modalidad->id_mad,
        $this->proceso,
    );

    expect(true)->toBeTrue();
});

test('a disabled carrera is rejected', function () {
    $this->procesoCarrera->update(['estado_pca' => EstadoCatalogo::Deshabilitado->value]);

    $this->validador->postulacionVigente(
        $this->procesoCarrera->id_sede,
        $this->procesoCarrera->id_carrera,
        $this->modalidad->id_mad,
        $this->proceso,
    );
})->throws(RuntimeException::class, 'La carrera seleccionada ya no se encuentra habilitada en esa sede para el proceso activo.');

test('a disabled modalidad is rejected', function () {
    ProcesoModalidad::query()
        ->where('id_proceso', $this->proceso->id_pro)
        ->where('id_modalidad_admision', $this->modalidad->id_mad)
        ->update(['estado_pmo' => EstadoCatalogo::Deshabilitado->value]);

    $this->validador->postulacionVigente(
        $this->procesoCarrera->id_sede,
        $this->procesoCarrera->id_carrera,
        $this->modalidad->id_mad,
        $this->proceso,
    );
})->throws(RuntimeException::class, 'La modalidad de la postulación ya no se encuentra habilitada para el proceso activo.');

test('a payment rule of the inscription process is accepted inside its validity window', function () {
    Carbon::setTestNow('2026-04-15 10:00:00');

    $reglaPago = ReglaPago::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();
    $inscripcion = (new Inscripcion)->forceFill([
        'id_proceso' => $this->proceso->id_pro,
        'id_modalidad_admision' => $this->modalidad->id_mad,
    ]);

    expect($this->validador->reglaPagoVigente($reglaPago->id_rpa, $inscripcion)->id_rpa)->toBe($reglaPago->id_rpa);
});

test('an expired payment rule is rejected with its end date', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');

    $reglaPago = ReglaPago::query()->where('id_proceso', $this->proceso->id_pro)->firstOrFail();
    $inscripcion = (new Inscripcion)->forceFill([
        'id_proceso' => $this->proceso->id_pro,
        'id_modalidad_admision' => $this->modalidad->id_mad,
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
