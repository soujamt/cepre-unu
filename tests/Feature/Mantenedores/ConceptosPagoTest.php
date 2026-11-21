<?php

use App\Models\Accion;
use App\Models\ConceptoPago;
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

function otorgarPermisosConceptoPago(Usuario $usuario, array $claves): void
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

function crearConceptoPagoPrueba(bool $permiteCuotas = false): ConceptoPago
{
    return ConceptoPago::query()->create([
        'codigo_cpg' => 'INS_CUO',
        'nombre_cpg' => 'INSCRIPCION MAS CUOTAS',
        'permite_cuotas_cpg' => $permiteCuotas,
        'estado_cpg' => 'HABILITADO',
    ]);
}

function crearReglaPagoDelConcepto(ConceptoPago $concepto, bool $permiteCuotas): ReglaPago
{
    $proceso = Proceso::query()->create([
        'codigo_pro' => '2026_1',
        'nombre_pro' => '2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    return ReglaPago::query()->create([
        'id_concepto_pago' => $concepto->id_cpg,
        'id_proceso' => $proceso->id_pro,
        'id_modalidad_admision' => null,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => $permiteCuotas,
        'monto_minimo_primera_cuota_rpa' => $permiteCuotas ? 200 : 0,
        'numero_maximo_cuotas_rpa' => $permiteCuotas ? 3 : 1,
        'requiere_pago_completo_rpa' => ! $permiteCuotas,
        'vigente_desde_rpa' => '2026-01-10',
        'estado_rpa' => 'HABILITADO',
    ]);
}

test('authorized users can access conceptos de pago page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice']);
    crearReglaPagoDelConcepto(crearConceptoPagoPrueba(permiteCuotas: true), permiteCuotas: true);

    $this->actingAs($usuario)
        ->get(route('conceptos-pago.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mantenedores/conceptos-pago')
            ->has('conceptos.data', 1)
            ->where('conceptos.data.0.codigo', 'INS_CUO')
            ->where('conceptos.data.0.permiteCuotas', true)
            ->where('conceptos.data.0.reglas', 1));
});

test('authenticated users without permiso cannot access conceptos de pago page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('conceptos-pago.index'))
        ->assertForbidden();
});

test('authorized users can register a concepto de pago that allows cuotas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_crear']);

    $this->actingAs($usuario)
        ->post(route('conceptos-pago.store'), [
            'codigo' => 'mat_cuo',
            'nombre' => 'Matrícula en cuotas',
            'descripcion' => ' Matrícula pagada  en partes ',
            'permite_cuotas' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Concepto de pago registrado correctamente.']);

    $this->assertDatabaseHas('tbl_concepto_pago', [
        'codigo_cpg' => 'MAT_CUO',
        'nombre_cpg' => 'MATRÍCULA EN CUOTAS',
        'descripcion_cpg' => 'Matrícula pagada en partes',
        'permite_cuotas_cpg' => true,
        'estado_cpg' => 'HABILITADO',
    ]);
});

test('concepto code must be unique', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_crear']);
    crearConceptoPagoPrueba();

    $this->actingAs($usuario)
        ->post(route('conceptos-pago.store'), ['codigo' => 'ins_cuo', 'nombre' => 'Otro concepto'])
        ->assertSessionHasErrors(['codigo' => 'Ya existe un concepto de pago con ese código.']);
});

test('cuotas cannot be turned off while reglas de pago in cuotas use the concepto', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_editar']);
    $concepto = crearConceptoPagoPrueba(permiteCuotas: true);
    crearReglaPagoDelConcepto($concepto, permiteCuotas: true);

    $this->actingAs($usuario)
        ->put(route('conceptos-pago.update', $concepto), ['codigo' => 'INS_CUO', 'nombre' => 'INSCRIPCION MAS CUOTAS', 'permite_cuotas' => false])
        ->assertSessionHasErrors(['permite_cuotas' => 'No se puede quitar porque hay reglas de pago en cuotas que usan este concepto.']);

    expect($concepto->fresh()->permite_cuotas_cpg)->toBeTrue();
});

test('cuotas can be turned off when its reglas de pago are single payment', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_editar']);
    $concepto = crearConceptoPagoPrueba(permiteCuotas: true);
    crearReglaPagoDelConcepto($concepto, permiteCuotas: false);

    $this->actingAs($usuario)
        ->put(route('conceptos-pago.update', $concepto), ['codigo' => 'INS_CUO', 'nombre' => 'INSCRIPCION MAS CUOTAS', 'permite_cuotas' => false])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Concepto de pago actualizado correctamente.');

    expect($concepto->fresh()->permite_cuotas_cpg)->toBeFalse();
});

test('concepto with reglas de pago cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_eliminar']);
    $concepto = crearConceptoPagoPrueba();
    crearReglaPagoDelConcepto($concepto, permiteCuotas: false)->delete();

    $this->actingAs($usuario)
        ->delete(route('conceptos-pago.destroy', $concepto))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', [
            'type' => 'error',
            'message' => 'El concepto tiene reglas de pago y no puede eliminarse. Puede deshabilitarlo.',
        ]);

    $this->assertNotSoftDeleted('tbl_concepto_pago', ['id_cpg' => $concepto->id_cpg], deletedAtColumn: 'eliminado_en');
});

test('authorized users can change state and delete an unused concepto', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConceptoPago($usuario, ['concepto_pago_indice', 'concepto_pago_estado', 'concepto_pago_eliminar']);
    $concepto = crearConceptoPagoPrueba();

    $this->actingAs($usuario)
        ->patch(route('conceptos-pago.estado', $concepto))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del concepto de pago actualizado.']);

    expect($concepto->fresh()->estado_cpg->value)->toBe('DESHABILITADO');

    $this->actingAs($usuario)
        ->delete(route('conceptos-pago.destroy', $concepto))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Concepto de pago eliminado correctamente.']);

    $this->assertSoftDeleted('tbl_concepto_pago', ['id_cpg' => $concepto->id_cpg], deletedAtColumn: 'eliminado_en');
});
