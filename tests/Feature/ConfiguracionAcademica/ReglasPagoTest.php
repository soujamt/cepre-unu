<?php

use App\Models\Accion;
use App\Models\ConceptoPago;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
use App\Models\ModalidadAdmision;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

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
 * @return array{semestre: Semestre, proceso: Proceso, conceptoPago: ConceptoPago, modalidad: ModalidadAdmision, convenioProceso: ConvenioProceso}
 */
function crearContextoReglaPago(int $indice, bool $permiteCuotasConcepto = true): array
{
    $semestre = Semestre::query()->create([
        'codigo_sem' => "semestre_2026_{$indice}",
        'nombre_sem' => "SEMESTRE 2026-{$indice}",
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
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

    $convenio = Convenio::query()->create([
        'codigo_con' => "CON{$indice}",
        'nombre_con' => "CONVENIO {$indice}",
        'descripcion_con' => 'Convenio de prueba',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 50,
        'estado_con' => 'HABILITADO',
    ]);

    $convenioProceso = ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-01-10',
        'fecha_fin_cpr' => '2026-03-15',
        'cupo_maximo_cpr' => 100,
        'estado_cpr' => 'HABILITADO',
    ]);

    return compact('semestre', 'proceso', 'conceptoPago', 'modalidad', 'convenioProceso');
}

test('authorized users can access reglas de pago page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice']);

    $this->actingAs($usuario)
        ->get(route('reglas-pago.index'))
        ->assertOk()
        ->assertSee('REGLAS DE PAGO');
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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.reglas-pago')
        ->set('reglaPagoForm.id_concepto_pago', (string) $contexto['conceptoPago']->id_cpg)
        ->set('reglaPagoForm.id_proceso', (string) $contexto['proceso']->id_pro)
        ->set('reglaPagoForm.id_modalidad_admision', (string) $contexto['modalidad']->id_mad)
        ->set('reglaPagoForm.id_convenio_proceso', (string) $contexto['convenioProceso']->id_cpr)
        ->set('reglaPagoForm.monto_total', '550.00')
        ->set('reglaPagoForm.permite_cuotas', true)
        ->set('reglaPagoForm.monto_minimo_primera_cuota', '275.00')
        ->set('reglaPagoForm.numero_maximo_cuotas', '3')
        ->set('reglaPagoForm.requiere_pago_completo', false)
        ->set('reglaPagoForm.vigente_desde', '2026-01-10')
        ->set('reglaPagoForm.vigente_hasta', '2026-03-15')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_concepto_pago' => $contexto['conceptoPago']->id_cpg,
        'id_proceso' => $contexto['proceso']->id_pro,
        'id_modalidad_admision' => $contexto['modalidad']->id_mad,
        'id_convenio_proceso' => $contexto['convenioProceso']->id_cpr,
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

test('authorized users can update a regla de pago', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosReglaPago($usuario, ['regla_pago_indice', 'regla_pago_editar']);

    $contextoA = crearContextoReglaPago(1);
    $contextoB = crearContextoReglaPago(2);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $contextoA['conceptoPago']->id_cpg,
        'id_proceso' => $contextoA['proceso']->id_pro,
        'id_modalidad_admision' => null,
        'id_convenio_proceso' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => true,
        'vigente_desde_rpa' => '2026-01-10',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.reglas-pago')
        ->call('cargarReglaPago', $reglaPago->id_rpa)
        ->set('reglaPagoForm.id_concepto_pago', (string) $contextoB['conceptoPago']->id_cpg)
        ->set('reglaPagoForm.id_proceso', (string) $contextoB['proceso']->id_pro)
        ->set('reglaPagoForm.id_modalidad_admision', (string) $contextoB['modalidad']->id_mad)
        ->set('reglaPagoForm.id_convenio_proceso', (string) $contextoB['convenioProceso']->id_cpr)
        ->set('reglaPagoForm.monto_total', '300.00')
        ->set('reglaPagoForm.permite_cuotas', false)
        ->set('reglaPagoForm.numero_maximo_cuotas', '1')
        ->set('reglaPagoForm.monto_minimo_primera_cuota', '')
        ->set('reglaPagoForm.requiere_pago_completo', true)
        ->set('reglaPagoForm.vigente_desde', '2026-02-01')
        ->set('reglaPagoForm.vigente_hasta', '2026-03-15')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_regla_pago', [
        'id_rpa' => $reglaPago->id_rpa,
        'id_concepto_pago' => $contextoB['conceptoPago']->id_cpg,
        'id_proceso' => $contextoB['proceso']->id_pro,
        'id_modalidad_admision' => $contextoB['modalidad']->id_mad,
        'id_convenio_proceso' => $contextoB['convenioProceso']->id_cpr,
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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.reglas-pago')
        ->call('cambiarEstado', $reglaPago->id_rpa)
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.reglas-pago')
        ->call('eliminar', $reglaPago->id_rpa)
        ->assertHasNoErrors();

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
        'id_convenio_proceso' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => false,
        'monto_minimo_primera_cuota_rpa' => 0,
        'numero_maximo_cuotas_rpa' => 1,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01',
        'estado_rpa' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.reglas-pago')
        ->set('reglaPagoForm.id_concepto_pago', (string) $contexto['conceptoPago']->id_cpg)
        ->set('reglaPagoForm.id_proceso', (string) $contexto['proceso']->id_pro)
        ->set('reglaPagoForm.id_modalidad_admision', '')
        ->set('reglaPagoForm.id_convenio_proceso', '')
        ->set('reglaPagoForm.monto_total', '550.00')
        ->set('reglaPagoForm.permite_cuotas', true)
        ->set('reglaPagoForm.monto_minimo_primera_cuota', '275.00')
        ->set('reglaPagoForm.numero_maximo_cuotas', '3')
        ->set('reglaPagoForm.requiere_pago_completo', false)
        ->set('reglaPagoForm.vigente_desde', '2026-01-01')
        ->call('guardar')
        ->assertHasErrors([
            'reglaPagoForm.permite_cuotas',
            'reglaPagoForm.id_proceso',
        ]);
});
