<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Exports\PagosExport;
use App\Models\Accion;
use App\Models\ImportacionBancoDetalle;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function otorgarPermisosPagosLista(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accionClave = Str::startsWith(Str::after($clave, 'pago_'), 'exportar') ? 'exportar_excel' : Str::afterLast($clave, '_');
        $accion = Accion::query()->where('clave_accion', $accionClave)->firstOrFail();
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => 'pago'],
            ['nombre_permiso' => 'PAGOS', 'estado_permiso' => 'HABILITADO'],
        );
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

function crearPagoManualPrueba(array $atributos = []): ImportacionBancoDetalle
{
    return ImportacionBancoDetalle::query()->create([
        'origen_ibd' => 'MANUAL',
        'numero_documento_ibd' => '47940211',
        'referencia_ibd' => 'RENGIFO',
        'numero_operacion_raw_ibd' => '54123',
        'numero_operacion_visible_ibd' => '54123',
        'numero_operacion_ibd' => '54123',
        'fecha_pago_ibd' => '2026-11-13',
        'monto_ibd' => 275,
        'ubicacion_ibd' => 'ADMISIÓN',
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
        ...$atributos,
    ]);
}

afterEach(fn () => Carbon::setTestNow());

test('authorized users can see the list of payments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice']);
    crearPagoManualPrueba();

    $this->actingAs($usuario)
        ->get(route('pagos.index'))
        ->assertOk()
        ->assertSee('Importaciones TXT')
        ->assertSee('54123')
        ->assertSee('LIBRE');
});

test('users without permiso cannot see the list of payments', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('pagos.index'))
        ->assertForbidden();
});

test('authorized users can register a manual payment from admision', function () {
    Carbon::setTestNow('2026-11-13 08:00:00');
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')
        ->call('cargarPago')
        ->set('pagoForm.dni', '47940211')
        ->set('pagoForm.referencia', 'rengifo del aguila')
        ->set('pagoForm.importe', '275')
        ->set('pagoForm.numero_operacion', '54123')
        ->set('pagoForm.ubicacion', 'ADMISIÓN')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Pago registrado correctamente.', type: 'success');

    $pago = ImportacionBancoDetalle::query()->sole();

    expect($pago->origen_ibd)->toBe('MANUAL')
        ->and($pago->referencia_ibd)->toBe('RENGIFO DEL AGUILA')
        ->and((float) $pago->monto_ibd)->toBe(275.0)
        ->and($pago->fecha_pago_ibd->toDateString())->toBe('2026-11-13')
        ->and($pago->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Pendiente)
        ->and($pago->id_importacion_banco)->toBeNull();
});

test('a zero payment can be registered for parentesco', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')
        ->set('pagoForm.dni', '61417851')
        ->set('pagoForm.importe', '0')
        ->set('pagoForm.numero_operacion', '452')
        ->set('pagoForm.ubicacion', 'PARENTESCO UNU')
        ->call('guardar')
        ->assertHasNoErrors();

    expect((float) ImportacionBancoDetalle::query()->sole()->monto_ibd)->toBe(0.0);
});

test('the same operation number cannot be repeated for the same dni', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_crear']);
    crearPagoManualPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')
        ->set('pagoForm.dni', '47940211')
        ->set('pagoForm.importe', '275')
        ->set('pagoForm.numero_operacion', '54123')
        ->call('guardar')
        ->assertHasErrors('pagoForm.numero_operacion');
});

test('a free manual payment can be edited and deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_editar', 'pago_eliminar']);
    $pago = crearPagoManualPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')
        ->call('cargarPago', $pago->id_ibd)
        ->assertSet('soloObservacion', false)
        ->set('pagoForm.importe', '500')
        ->call('guardar')
        ->assertHasNoErrors()
        ->call('eliminar', $pago->id_ibd)
        ->assertDispatched('toast', message: 'Pago eliminado correctamente.', type: 'success');

    expect((float) $pago->fresh()?->monto_ibd)->toBe(500.0);
    $this->assertSoftDeleted('tbl_importacion_banco_detalle', ['id_ibd' => $pago->id_ibd], deletedAtColumn: 'eliminado_en');
});

test('a registered payment only allows editing its observation', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_editar', 'pago_eliminar']);
    $pago = crearPagoManualPrueba(['estado_ibd' => EstadoImportacionBancoDetalle::Asociado->value]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')
        ->call('cargarPago', $pago->id_ibd)
        ->assertSet('soloObservacion', true)
        ->set('pagoForm.importe', '1')
        ->set('pagoForm.observacion', 'solicitó devolución')
        ->call('guardar')
        ->assertHasNoErrors()
        ->call('eliminar', $pago->id_ibd)
        ->assertDispatched('toast', message: 'Solo se pueden eliminar pagos registrados a mano que sigan libres.', type: 'error');

    $pago->refresh();

    expect((float) $pago->monto_ibd)->toBe(275.0)
        ->and($pago->observacion_ibd)->toBe('SOLICITÓ DEVOLUCIÓN')
        ->and($pago->trashed())->toBeFalse();
});

test('authorized users can export the payments', function () {
    Carbon::setTestNow('2026-11-17 10:00:00');
    Excel::fake();
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_exportar_excel']);
    crearPagoManualPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-lista')->call('exportarExcel');

    Excel::assertDownloaded('pagos-cepre-20261117-100000.xlsx', function (PagosExport $export): bool {
        $fila = $export->collection()->first();

        expect($fila[2])->toBe('47940211')
            ->and($fila[6])->toBe('ADMISIÓN')
            ->and($fila[7])->toBe('LIBRE');

        return true;
    });
});
