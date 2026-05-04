<?php

use App\Enums\Contracts\PresentableEnum;
use App\Enums\EstadoCatalogo;
use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoProceso;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoUsuario;
use App\Enums\EstadoValidacionPago;
use App\Enums\SexoPersona;
use App\Enums\TipoBeneficioConvenio;

dataset('presentable-enums', [
    EstadoCatalogo::class,
    EstadoImportacionBanco::class,
    EstadoImportacionBancoDetalle::class,
    EstadoInscripcion::class,
    EstadoObligacionPago::class,
    EstadoProceso::class,
    EstadoRevisionExpediente::class,
    EstadoUsuario::class,
    EstadoValidacionPago::class,
    SexoPersona::class,
    TipoBeneficioConvenio::class,
]);

it('builds reusable option arrays for presentable enums', function (string $enumClass): void {
    expect(is_subclass_of($enumClass, PresentableEnum::class))->toBeTrue();

    $cases = $enumClass::cases();
    $values = $enumClass::values();
    $options = $enumClass::options();
    $labels = $enumClass::labels();

    expect($values)->toHaveCount(count($cases))
        ->and($options)->toHaveCount(count($cases))
        ->and($labels)->toHaveCount(count($cases));

    foreach ($cases as $index => $case) {
        expect($values[$index])->toBe($case->value)
            ->and($options[$case->value])->toBe($case->getNombre())
            ->and($labels[$index])->toBe($case->toArray())
            ->and($case->toArray())->toMatchArray([
                'value' => $case->value,
                'nombre' => $case->getNombre(),
                'color' => $case->getColor(),
                'icon' => $case->getIcon(),
                'icono' => $case->getIcono(),
            ])
            ->and($case->toBadge())->toMatchArray([
                'label' => $case->getNombre(),
                'color' => $case->getColor(),
                'icon' => $case->getIcon(),
            ]);
    }
})->with('presentable-enums');
