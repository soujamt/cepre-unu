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
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('pagos/pagos')
            ->has('pagos.data', 1)
            ->where('pagos.data.0.operacion', '54123')
            ->where('pagos.data.0.estado', 'LIBRE')
            ->where('pagos.data.0.editable', true)
            ->where('resumen.libres', 1));
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

    $this->actingAs($usuario)
        ->post(route('pagos.store'), [
            'fecha' => '2026-11-13',
            'dni' => '47940211',
            'referencia' => 'rengifo del aguila',
            'importe' => '275',
            'numero_operacion' => '54123',
            'ubicacion' => 'ADMISIÓN',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Pago registrado correctamente.']);

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

    $this->actingAs($usuario)
        ->post(route('pagos.store'), ['fecha' => now()->toDateString(), 'dni' => '61417851', 'importe' => '0', 'numero_operacion' => '452', 'ubicacion' => 'PARENTESCO UNU'])
        ->assertSessionHasNoErrors();

    expect((float) ImportacionBancoDetalle::query()->sole()->monto_ibd)->toBe(0.0);
});

test('the same operation number cannot be repeated for the same dni', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_crear']);
    crearPagoManualPrueba();

    $this->actingAs($usuario)
        ->post(route('pagos.store'), ['fecha' => now()->toDateString(), 'dni' => '47940211', 'importe' => '275', 'numero_operacion' => '54123', 'ubicacion' => 'ADMISIÓN'])
        ->assertSessionHasErrors(['numero_operacion' => 'Ya existe un pago con ese número de operación para el mismo DNI.']);
});

test('a free manual payment can be edited and deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_editar', 'pago_eliminar']);
    $pago = crearPagoManualPrueba();

    $this->actingAs($usuario)
        ->put(route('pagos.update', $pago), [
            'fecha' => $pago->fecha_pago_ibd->toDateString(),
            'dni' => $pago->numero_documento_ibd,
            'importe' => '500',
            'numero_operacion' => $pago->numero_operacion_visible_ibd,
            'ubicacion' => 'ADMISIÓN',
        ])
        ->assertSessionHasNoErrors();

    expect((float) $pago->fresh()?->monto_ibd)->toBe(500.0);

    $this->actingAs($usuario)
        ->delete(route('pagos.destroy', $pago))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Pago eliminado correctamente.']);

    $this->assertSoftDeleted('tbl_importacion_banco_detalle', ['id_ibd' => $pago->id_ibd], deletedAtColumn: 'eliminado_en');
});

test('a registered payment only allows editing its observation', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagosLista($usuario, ['pago_indice', 'pago_editar', 'pago_eliminar']);
    $pago = crearPagoManualPrueba(['estado_ibd' => EstadoImportacionBancoDetalle::Asociado->value]);

    $this->actingAs($usuario)
        ->put(route('pagos.update', $pago), ['importe' => '1', 'observacion' => 'solicitó devolución'])
        ->assertSessionHasNoErrors();

    $this->actingAs($usuario)
        ->delete(route('pagos.destroy', $pago))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'error', 'message' => 'Solo se pueden eliminar pagos registrados a mano que sigan libres.']);

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

    $this->actingAs($usuario)->get(route('pagos.exportar'));

    Excel::assertDownloaded('pagos-cepre-20261117-100000.xlsx', function (PagosExport $export): bool {
        $fila = $export->collection()->first();

        expect($fila[2])->toBe('47940211')
            ->and($fila[6])->toBe('ADMISIÓN')
            ->and($fila[7])->toBe('LIBRE');

        return true;
    });
});
