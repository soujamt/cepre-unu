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

function otorgarPermisosAula(Usuario $usuario, array $claves): void
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

function crearAulaPrueba(string $nombre = 'AULA 101', int $orden = 1): Aula
{
    return Aula::query()->create([
        'nombre_aul' => $nombre,
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => $orden,
        'estado_aul' => 'HABILITADO',
    ]);
}

test('authorized users can access aulas page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice']);
    crearAulaPrueba('AULA 202', 2);
    crearAulaPrueba('AULA 101', 1);

    $this->actingAs($usuario)
        ->get(route('mantenedor-aulas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mantenedores/aulas')
            ->has('aulas.data', 2)
            ->where('aulas.data.0.nombre', 'AULA 101')
            ->where('aulas.data.0.lugar', 'UNU')
            ->has('pisos', 5)
            ->has('lugares', 2));
});

test('authenticated users without permiso cannot access aulas page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('mantenedor-aulas.index'))
        ->assertForbidden();
});

test('authorized users can register an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_crear']);

    $this->actingAs($usuario)
        ->post(route('mantenedor-aulas.store'), ['nombre' => 'Aula  101', 'piso' => 'PISO 1', 'lugar' => 'UNU', 'orden' => '1'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Aula registrada correctamente.']);

    $this->assertDatabaseHas('tbl_aula', [
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('aula requires a valid piso, lugar and orden', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_crear']);

    $this->actingAs($usuario)
        ->post(route('mantenedor-aulas.store'), ['nombre' => 'Aula 101', 'piso' => 'PISO 9', 'lugar' => '', 'orden' => '0'])
        ->assertSessionHasErrors([
            'piso' => 'Seleccione un piso válido.',
            'lugar' => 'El lugar es obligatorio.',
            'orden' => 'El orden debe ser mayor o igual a 1.',
        ]);
});

test('authorized users can update an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_editar']);
    $aula = crearAulaPrueba();

    $this->actingAs($usuario)
        ->put(route('mantenedor-aulas.update', $aula), ['nombre' => 'Aula 201', 'piso' => 'PISO 2', 'lugar' => 'UAP', 'orden' => '2'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Aula actualizada correctamente.');

    $this->assertDatabaseHas('tbl_aula', [
        'id_aul' => $aula->id_aul,
        'nombre_aul' => 'AULA 201',
        'piso_aul' => 'PISO 2',
        'lugar_aul' => 'UAP',
        'orden_aul' => 2,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_estado']);
    $aula = crearAulaPrueba();

    $this->actingAs($usuario)
        ->patch(route('mantenedor-aulas.estado', $aula))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del aula actualizado.']);

    $this->assertDatabaseHas('tbl_aula', [
        'id_aul' => $aula->id_aul,
        'estado_aul' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete an aula without assignments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_eliminar']);
    $aula = crearAulaPrueba();

    $this->actingAs($usuario)
        ->delete(route('mantenedor-aulas.destroy', $aula))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Aula eliminada correctamente.']);

    $aulaEliminada = Aula::query()->withTrashed()->find($aula->id_aul);

    expect($aulaEliminada?->trashed())->toBeTrue()
        ->and($aulaEliminada?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('authenticated users cannot register duplicated aulas by name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_crear']);
    crearAulaPrueba();

    $this->actingAs($usuario)
        ->post(route('mantenedor-aulas.store'), ['nombre' => 'aula 101', 'piso' => 'PISO 2', 'lugar' => 'UAP', 'orden' => '2'])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un aula con ese nombre.']);
});

test('authenticated users cannot delete an aula with proceso assignments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_eliminar']);

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_1',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => 'turno_1',
        'nombre_tur' => 'MANANA',
        'estado_tur' => 'HABILITADO',
    ]);

    $aula = crearAulaPrueba();

    ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('mantenedor-aulas.destroy', $aula))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'error', 'message' => 'El aula tiene asignaciones por proceso y no puede eliminarse.']);

    expect(Aula::query()->find($aula->id_aul))->not->toBeNull();
});
