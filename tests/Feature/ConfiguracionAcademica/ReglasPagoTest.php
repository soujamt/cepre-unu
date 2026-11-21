<?php

use App\Models\Accion;
use App\Models\ConceptoPago;
use App\Models\ModalidadAdmision;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function otorgarPermisosReglaPago(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $permisoClave = Str::beforeLast($clave, '_');
        $accionClave = Str::afterLast($clave, '_');

        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $permisoClave],
            [
                'nombre_permiso' => Str::upper(str_replace('_', ' ', $permisoClave)),
                'estado_permiso' => 'HABILITADO',
            ],
        );

        $accion = Accion::query()->where('clave_accion', $accionClave)->firstOrFail();

        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            [
                'id_permiso' => $permiso->id_permiso,
                'id_accion' => $accion->id_acc,
            ],
            [
                'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
                'estado_peracc' => 'HABILITADO',
                'eliminado_en' => null,
            ],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

/**
 * @return array{proceso: Proceso, conceptoPago: ConceptoPago, modalidad: ModalidadAdmision}
 */
function crearContextoReglaPago(int $indice, bool $permiteCuotasConcepto = true): array
{

    $proceso = Proceso::query()->create([
        'codigo_pro' => "proceso_{$indice}",
        'nombre_pro' => "PROCESO {$indice}",
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $conceptoPago = ConceptoPago::query()->create([
        'codigo_cpg' => "CPG{$indice}",
        'nombre_cpg' => "CONCEPTO {$indice}",
        'descripcion_cpg' => 'Concepto de prueba',
        'permite_cuotas_cpg' => $permiteCuotasConcepto,
        'estado_cpg' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => "MAD{$indice}",
        'nombre_mad' => "MODALIDAD {$indice}",
        'estado_mad' => 'HABILITADO',
    ]);

    return compact('proceso', 'conceptoPago', 'modalidad');
}

test('authorized users can access reglas de pago page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice']);

    $this->actingAs($usuario)
        ->get(route('reglas-pago.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('configuracion-academica/reglas-pago')->has('reglas.data')->has('documentos', 2));
});

test('authenticated users without permiso cannot access reglas de pago page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('reglas-pago.index'))
        ->assertForbidden();
});

test('authorized users can register a regla de pago', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_crear']);

    $contexto = crearContextoReglaPago(1);

    $this->actingAs($usuario)
        ->post(route('reglas-pago.store'), [
            'id_concepto_pago' => (string) $contexto['conceptoPago']->id_cpg,
            'id_proceso' => (string) $contexto['proceso']->id_pro,
            'id_modalidad_admision' => (string) $contexto['modalidad']->id_mad,
            'monto_total' => '550.00',
            'permite_cuotas' => true,
            'monto_minimo_primera_cuota' => '275.00',
            'numero_maximo_cuotas' => '3',
            'requiere_pago_completo' => false,
            'vigente_desde' => '2026-01-10',
            'vigente_hasta' => '2026-03-15',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
        'id_proceso' => $contexto['proceso']->id_pro,
        'id_modalidad_admision' => $contexto['modalidad']->id_mad,
        'monto_total_rpa' => 550.00,
        'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 275.00,
        'numero_maximo_cuotas_rpa' => 3,
        'requiere_pago_completo_rpa' => false,
        'estado_rpa' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('new enabled procesos are available in regla pago form even without reglas registradas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_crear']);

    $contexto = crearContextoReglaPago(9);

    $this->actingAs($usuario)
        ->get(route('reglas-pago.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('procesos', fn ($procesos): bool => collect($procesos)
            ->contains(fn (array $proceso): bool => $proceso['nombre'] === $contexto['proceso']->nombre_pro && $proceso['habilitado'] === true)));
});

test('regla pago filters include the current enabled proceso and the historical proceso with reglas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice']);

    $contextoHistorico = crearContextoReglaPago(10);
    $contextoHistorico['proceso']->update(['estado_pro' => 'CERRADO']);

    ReglaPago::query()->create([
        'id_concepto_pago' => $contextoHistorico['conceptoPago']->id_cpg,
        'id_proceso' => $contextoHistorico['proceso']->id_pro,
        'id_modalidad_admision' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => true,
        'vigente_desde_rpa' => '2026-01-10',
        'vigente_hasta_rpa' => '2026-03-15',
        'estado_rpa' => 'HABILITADO',
    ]);

    $contextoActual = crearContextoReglaPago(11);

    $this->actingAs($usuario)
        ->get(route('reglas-pago.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('procesosFiltro', fn ($procesos): bool => collect($procesos)->pluck('nombre')->contains($contextoHistorico['proceso']->nombre_pro)
            && collect($procesos)->pluck('nombre')->contains($contextoActual['proceso']->nombre_pro)));
});

test('authorized users can update a regla de pago', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_editar']);

    $contextoA = crearContextoReglaPago(1);
    $contextoB = crearContextoReglaPago(2);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $contextoA['conceptoPago']->id_cpg,
        'id_proceso' => $contextoA['proceso']->id_pro,
        'id_modalidad_admision' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => true,
        'vigente_desde_rpa' => '2026-01-10',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->put(route('reglas-pago.update', $reglaPago), [
            'id_concepto_pago' => (string) $contextoB['conceptoPago']->id_cpg,
            'id_proceso' => (string) $contextoB['proceso']->id_pro,
            'id_modalidad_admision' => (string) $contextoB['modalidad']->id_mad,
            'monto_total' => '300.00',
            'permite_cuotas' => false,
            'numero_maximo_cuotas' => '1',
            'monto_minimo_primera_cuota' => '',
            'requiere_pago_completo' => true,
            'vigente_desde' => '2026-02-01',
            'vigente_hasta' => '2026-03-15',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_rpa' => $reglaPago->id_rpa,
        'id_concepto_pago' => $contextoB['conceptoPago']->id_cpg,
        'id_proceso' => $contextoB['proceso']->id_pro,
        'id_modalidad_admision' => $contextoB['modalidad']->id_mad,
        'monto_total_rpa' => 300.00,
        'permite_cuotas_rpa' => false,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => true,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a regla de pago', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_estado']);

    $contexto = crearContextoReglaPago(1);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
        'id_proceso' => $contexto['proceso']->id_pro,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->patch(route('reglas-pago.estado', $reglaPago))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado de la regla de pago actualizado.']);

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_rpa' => $reglaPago->id_rpa,
        'estado_rpa' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a regla de pago without obligaciones relacionadas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_eliminar']);

    $contexto = crearContextoReglaPago(1);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
        'id_proceso' => $contexto['proceso']->id_pro,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('reglas-pago.destroy', $reglaPago))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Regla de pago eliminada correctamente.']);

    $reglaPagoEliminada = ReglaPago::withTrashed()->find($reglaPago->id_rpa);

    expect($reglaPagoEliminada)->not->toBeNull();
    expect($reglaPagoEliminada?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('regla de pago validates duplicate context and cuotas availability', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_crear']);

    $contexto = crearContextoReglaPago(1, false);

    ReglaPago::query()->create([
        'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
        'id_proceso' => $contexto['proceso']->id_pro,
        'id_modalidad_admision' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->post(route('reglas-pago.store'), [
            'id_concepto_pago' => (string) $contexto['conceptoPago']->id_cpg,
            'id_proceso' => (string) $contexto['proceso']->id_pro,
            'id_modalidad_admision' => '',
            'monto_total' => '550.00',
            'permite_cuotas' => true,
            'monto_minimo_primera_cuota' => '275.00',
            'numero_maximo_cuotas' => '3',
            'requiere_pago_completo' => false,
            'vigente_desde' => '2026-01-01',
        ])
        ->assertSessionHasErrors(['permite_cuotas', 'id_proceso']);
});

test('a regla de pago can be free and require a document number', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_crear']);

    $contexto = crearContextoReglaPago(12, false);

    $this->actingAs($usuario)
        ->post(route('reglas-pago.store'), [
            'id_concepto_pago' => (string) $contexto['conceptoPago']->id_cpg,
            'id_proceso' => (string) $contexto['proceso']->id_pro,
            'monto_total' => '0',
            'documento_requerido' => 'N° DE CONSTANCIA',
            'vigente_desde' => '2026-01-10',
            'vigente_hasta' => '2026-03-15',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_proceso' => $contexto['proceso']->id_pro,
        'monto_total_rpa' => 0,
        'documento_requerido_rpa' => 'N° DE CONSTANCIA',
    ]);
});

test('a regla de pago in cuotas needs at least two cuotas and a first cuota within the total', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_crear']);
    $contexto = crearContextoReglaPago(13);

    $this->actingAs($usuario)
        ->post(route('reglas-pago.store'), [
            'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
            'id_proceso' => $contexto['proceso']->id_pro,
            'monto_total' => '300',
            'permite_cuotas' => true,
            'monto_minimo_primera_cuota' => '400',
            'numero_maximo_cuotas' => '1',
            'vigente_desde' => '2025-12-01',
        ])
        ->assertSessionHasErrors([
            'monto_minimo_primera_cuota' => 'El monto mínimo de la primera cuota no puede ser mayor al monto total.',
            'numero_maximo_cuotas' => 'Debe indicar al menos 2 cuotas cuando la regla permite cuotas.',
            'vigente_desde' => 'La vigencia inicial no puede ser menor que la fecha de inicio del proceso.',
        ]);
});
