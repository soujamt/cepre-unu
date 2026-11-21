<?php

use App\Models\Accion;
use App\Models\Aula;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Rol;
use App\Models\Turno;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function otorgarPermisosAulaProceso(Usuario $usuario, array $claves): void
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

        $accion = Accion::query()->firstOrCreate(
            ['clave_accion' => $accionClave],
            [
                'nombre_accion' => Str::upper($accionClave),
                'estado_accion' => 'HABILITADO',
            ],
        );

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
 * @return array{proceso: Proceso, aula: Aula, turno: Turno, asignacion: ProcesoAula}
 */
function crearAulaProcesoContexto(int $indice, string $estado = 'HABILITADO'): array
{
    $proceso = Proceso::query()->create([
        'codigo_pro' => "proceso_ordinario_2026_{$indice}",
        'nombre_pro' => "PROCESO ORDINARIO 2026-{$indice}",
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $aula = Aula::query()->create([
        'nombre_aul' => "AULA {$indice}",
        'piso_aul' => 'PISO 1',
        'lugar_aul' => $indice === 1 ? 'UNU' : 'UAP',
        'orden_aul' => $indice,
        'estado_aul' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "turno_{$indice}",
        'nombre_tur' => $indice === 1 ? 'MANANA' : 'TARDE',
        'estado_tur' => 'HABILITADO',
    ]);

    $asignacion = ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => $estado,
    ]);

    return compact('proceso', 'aula', 'turno', 'asignacion');
}

test('authorized users can access aulas por proceso page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice']);

    $this->actingAs($usuario)
        ->get(route('aulas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('configuracion-academica/aulas-proceso')->has('asignaciones.data'));
});

test('users can filter aulas por proceso by proceso turno and estado', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice']);

    $contextoA = crearAulaProcesoContexto(1, 'HABILITADO');
    $contextoB = crearAulaProcesoContexto(2, 'DESHABILITADO');

    $soloB = fn (array $consulta) => $this->actingAs($usuario)
        ->get(route('aulas.index', $consulta))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('asignaciones.data', 1)->where('asignaciones.data.0.aula', $contextoB['aula']->nombre_aul));

    $this->actingAs($usuario)
        ->get(route('aulas.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('asignaciones.data', 2));

    $soloB(['proceso' => $contextoB['proceso']->id_pro]);
    $soloB(['turno' => $contextoB['turno']->id_tur]);
    $soloB(['estado' => ['DESHABILITADO']]);
});

test('asignar aulas lists every proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contextoA = crearAulaProcesoContexto(1);
    $contextoB = crearAulaProcesoContexto(2);

    $this->actingAs($usuario)
        ->get(route('aulas.asignar', ['proceso' => $contextoA['proceso']->id_pro, 'turno' => $contextoA['turno']->id_tur]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('configuracion-academica/aulas-proceso-asignar')
            ->where('procesos', fn ($procesos): bool => collect($procesos)->pluck('nombre')->contains($contextoA['proceso']->nombre_pro)
                && collect($procesos)->pluck('nombre')->contains($contextoB['proceso']->nombre_pro))
            ->where('aulas', fn ($aulas): bool => collect($aulas)->firstWhere('id', $contextoA['aula']->id_aul)['asignada'] === true));
});

test('asignar aulas allows the same aula in different turnos for one proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contexto = crearAulaProcesoContexto(1);

    $turnoTarde = Turno::query()->create([
        'codigo_tur' => 'turno_tarde',
        'nombre_tur' => 'TARDE',
        'estado_tur' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->post(route('aulas.store'), [
            'proceso' => $contexto['proceso']->id_pro,
            'turno' => $turnoTarde->id_tur,
            'aulas' => [['id' => $contexto['aula']->id_aul, 'capacidad' => '50']],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Aulas asignadas correctamente.']);

    expect(ProcesoAula::query()
        ->where('id_proceso', $contexto['proceso']->id_pro)
        ->where('id_aula', $contexto['aula']->id_aul)
        ->count())->toBe(2);

    expect(ProcesoAula::query()
        ->where('id_proceso', $contexto['proceso']->id_pro)
        ->where('id_turno', $turnoTarde->id_tur)
        ->where('id_aula', $contexto['aula']->id_aul)
        ->value('capacidad_pau'))->toBe(50);
});

test('asignar aulas restores a soft deleted assignment for the same proceso turno and aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contexto = crearAulaProcesoContexto(1);
    $contexto['asignacion']->delete();

    $this->actingAs($usuario)
        ->post(route('aulas.store'), [
            'proceso' => $contexto['proceso']->id_pro,
            'turno' => $contexto['turno']->id_tur,
            'aulas' => [['id' => $contexto['aula']->id_aul, 'capacidad' => '55']],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Aulas asignadas correctamente.']);

    $asignacionRestaurada = ProcesoAula::query()
        ->withTrashed()
        ->findOrFail($contexto['asignacion']->id_pau);

    expect($asignacionRestaurada->trashed())->toBeFalse()
        ->and($asignacionRestaurada->capacidad_pau)->toBe(55)
        ->and($asignacionRestaurada->estado_pau->value)->toBe('HABILITADO');
});

test('asignar aulas requires proceso, turno and a valid capacity', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);
    $contexto = crearAulaProcesoContexto(1);

    $this->actingAs($usuario)
        ->post(route('aulas.store'), ['proceso' => '', 'turno' => '', 'aulas' => [['id' => $contexto['aula']->id_aul, 'capacidad' => '0']]])
        ->assertSessionHasErrors([
            'proceso' => 'Debe seleccionar un proceso.',
            'turno' => 'Debe seleccionar un turno.',
            'aulas.0.capacidad' => 'Todas las aulas seleccionadas deben tener una capacidad válida (mayor a 0).',
        ]);
});

test('authorized users can update the capacity, state and delete an assignment without inscripciones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice', 'aula_proceso_editar', 'aula_proceso_estado', 'aula_proceso_eliminar']);
    $contexto = crearAulaProcesoContexto(1);

    $this->actingAs($usuario)
        ->put(route('aulas.update', $contexto['asignacion']), ['capacidad' => '35'])
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Capacidad actualizada correctamente.']);

    $this->actingAs($usuario)
        ->patch(route('aulas.estado', $contexto['asignacion']))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado actualizado.']);

    expect($contexto['asignacion']->fresh()->capacidad_pau)->toBe(35)
        ->and($contexto['asignacion']->fresh()->estado_pau->value)->toBe('DESHABILITADO');

    $this->actingAs($usuario)
        ->delete(route('aulas.destroy', $contexto['asignacion']))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Asignación eliminada correctamente.']);

    expect($contexto['asignacion']->fresh()->trashed())->toBeTrue();
});
