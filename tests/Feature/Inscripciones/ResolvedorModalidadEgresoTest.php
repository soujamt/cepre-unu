<?php

use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoModalidad;
use App\Support\ResolvedorModalidadEgreso;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proceso = Proceso::query()->create([
        'codigo_pro' => '2027_2',
        'nombre_pro' => '2027-2',
        'fecha_inicio_pro' => '2026-09-01 00:00:00',
        'fecha_fin_pro' => '2026-12-31 23:59:59',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->ordinario = ModalidadAdmision::query()->where('codigo_mad', 'ORD')->firstOrFail();
    $this->reserva = ModalidadAdmision::query()->where('codigo_mad', 'EXT')->firstOrFail();

    foreach ([$this->ordinario, $this->reserva] as $modalidad) {
        ProcesoModalidad::query()->create([
            'id_proceso' => $this->proceso->id_pro,
            'id_modalidad_admision' => $modalidad->id_mad,
            'estado_pmo' => 'HABILITADO',
        ]);
    }

    $this->resolvedor = app(ResolvedorModalidadEgreso::class);
});

test('a student who already finished school is ordinario', function () {
    expect($this->resolvedor->resolver($this->proceso, 2025)?->id_mad)->toBe($this->ordinario->id_mad);
});

test('a student still in school is extraordinario o reserva', function () {
    expect($this->resolvedor->resolver($this->proceso, 2026)?->id_mad)->toBe($this->reserva->id_mad)
        ->and($this->resolvedor->resolver($this->proceso, 2028)?->id_mad)->toBe($this->reserva->id_mad);
});

test('the configured maximum year moves the cut', function () {
    $this->proceso->update(['anio_egreso_max_ordinario_pro' => 2026]);

    expect($this->resolvedor->resolver($this->proceso, 2026)?->id_mad)->toBe($this->ordinario->id_mad)
        ->and($this->resolvedor->resolver($this->proceso, 2027)?->id_mad)->toBe($this->reserva->id_mad);
});

test('without the modalidad enabled in the proceso there is no result', function () {
    ProcesoModalidad::query()
        ->where('id_modalidad_admision', $this->reserva->id_mad)
        ->update(['estado_pmo' => 'DESHABILITADO']);

    expect($this->resolvedor->resolver($this->proceso, 2026))->toBeNull();
});
