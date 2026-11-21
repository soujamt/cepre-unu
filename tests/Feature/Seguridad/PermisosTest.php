<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function otorgarPermisos(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        [$permisoClave, $accionClave] = explode('_', $clave, 2);

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

test('authenticated users with permiso can access the permisos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice']);

    $this->actingAs($usuario)
        ->get(route('permisos.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('seguridad/permisos')->has('permisos.data')->has('acciones'));
});

test('authenticated users without permiso cannot access the permisos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('permisos.index'))
        ->assertForbidden();
});

test('authenticated users can register a permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_crear']);
    $acciones = Accion::query()
        ->whereIn('clave_accion', ['indice', 'crear'])
        ->pluck('id_acc')
        ->map(fn (int $id): string => (string) $id)
        ->all();

    $this->actingAs($usuario)
        ->post(route('permisos.store'), ['nombre' => 'Ver  permisos', 'acciones' => $acciones])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Permiso registrado correctamente.']);

    $this->assertDatabaseHas('tbl_permiso', [
        'clave_permiso' => 'ver_permisos',
        'nombre_permiso' => 'VER PERMISOS',
        'estado_permiso' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $permiso = Permiso::query()->where('clave_permiso', 'ver_permisos')->firstOrFail();

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'clave_peracc' => 'ver_permisos_indice',
        'estado_peracc' => 'HABILITADO',
    ]);

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'clave_peracc' => 'ver_permisos_crear',
        'estado_peracc' => 'HABILITADO',
    ]);
});

test('authenticated users can update a permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_editar']);
    $permiso = Permiso::query()->create([
        'clave_permiso' => 'ver_permisos',
        'nombre_permiso' => 'VER PERMISOS',
        'estado_permiso' => 'HABILITADO',
    ]);
    $accionesIniciales = Accion::query()
        ->whereIn('clave_accion', ['indice', 'crear'])
        ->get();
    $accionesActualizadas = Accion::query()
        ->whereIn('clave_accion', ['indice', 'editar', 'exportar_pdf'])
        ->pluck('id_acc')
        ->map(fn (int $id): string => (string) $id)
        ->all();

    foreach ($accionesIniciales as $accion) {
        $permiso->permisoAcciones()->create([
            'id_accion' => $accion->id_acc,
            'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
            'estado_peracc' => 'HABILITADO',
        ]);
    }

    $this->actingAs($usuario)
        ->put(route('permisos.update', $permiso), ['nombre' => 'Gestionar permisos', 'acciones' => $accionesActualizadas])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Permiso actualizado correctamente.');

    $this->assertDatabaseHas('tbl_permiso', [
        'id_permiso' => $permiso->id_permiso,
        'clave_permiso' => 'gestionar_permisos',
        'nombre_permiso' => 'GESTIONAR PERMISOS',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'clave_peracc' => 'gestionar_permisos_indice',
        'estado_peracc' => 'HABILITADO',
    ]);

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'clave_peracc' => 'gestionar_permisos_editar',
        'estado_peracc' => 'HABILITADO',
    ]);

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'clave_peracc' => 'gestionar_permisos_exportar_pdf',
        'estado_peracc' => 'HABILITADO',
    ]);

    $this->assertDatabaseHas('tbl_permiso_accion', [
        'id_permiso' => $permiso->id_permiso,
        'id_accion' => Accion::query()->where('clave_accion', 'crear')->value('id_acc'),
        'estado_peracc' => 'DESHABILITADO',
    ]);
});

test('authenticated users cannot register duplicated permisos by generated code', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_crear']);
    $acciones = Accion::query()
        ->where('clave_accion', 'indice')
        ->pluck('id_acc')
        ->map(fn (int $id): string => (string) $id)
        ->all();

    Permiso::query()->create([
        'clave_permiso' => 'gestionar_permisos',
        'nombre_permiso' => 'GESTIONAR PERMISOS',
        'estado_permiso' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->post(route('permisos.store'), ['nombre' => 'Gestionar   permisos', 'acciones' => $acciones])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un permiso con ese nombre.']);
});

test('authenticated users must select at least one action for a permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_crear']);

    $this->actingAs($usuario)
        ->post(route('permisos.store'), ['nombre' => 'Usuarios', 'acciones' => []])
        ->assertSessionHasErrors(['acciones' => 'Debe seleccionar al menos una acción.']);
});

test('authenticated users can change estado of a permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_estado']);
    $permiso = Permiso::query()->create([
        'clave_permiso' => 'usuarios',
        'nombre_permiso' => 'USUARIOS',
        'estado_permiso' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->patch(route('permisos.estado', $permiso))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del permiso actualizado.']);

    $this->assertDatabaseHas('tbl_permiso', [
        'id_permiso' => $permiso->id_permiso,
        'estado_permiso' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authenticated users can delete a permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice', 'permiso_eliminar']);
    $permiso = Permiso::query()->create([
        'clave_permiso' => 'usuarios',
        'nombre_permiso' => 'USUARIOS',
        'estado_permiso' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('permisos.destroy', $permiso))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Permiso eliminado correctamente.']);

    expect(Permiso::query()->find($permiso->id_permiso))->toBeNull();
    $permisoEliminado = Permiso::withTrashed()->find($permiso->id_permiso);

    expect($permisoEliminado)->not->toBeNull();
    expect($permisoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('disabled parent permiso no longer grants access even if permiso accion is enabled', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisos($usuario, ['permiso_indice']);

    Permiso::query()
        ->where('clave_permiso', 'permiso')
        ->update(['estado_permiso' => 'DESHABILITADO']);

    $this->actingAs($usuario)
        ->get(route('permisos.index'))
        ->assertForbidden();
});

test('administrador role can access permisos module without explicit permiso assignment', function () {
    $usuario = Usuario::factory()->create();
    $rol = Rol::query()->create([
        'nombre_rol' => 'ADMINISTRADOR',
        'descripcion_rol' => 'Superadministrador',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    $this->actingAs($usuario)
        ->get(route('permisos.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('seguridad/permisos')->has('permisos.data')->has('acciones'));
});
