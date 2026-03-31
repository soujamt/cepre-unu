<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authenticated users can access the permisos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('permisos.index'))
        ->assertOk()
        ->assertSee('PERMISOS');
});

test('authenticated users can register a permiso', function () {
    $usuario = Usuario::factory()->create();
    $acciones = Accion::query()
        ->whereIn('clave_accion', ['indice', 'crear'])
        ->pluck('id_acc')
        ->map(fn (int $id): string => (string) $id)
        ->all();

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->set('permisoForm.nombre', 'Ver permisos')
        ->set('permisoForm.acciones', $acciones)
        ->call('guardar')
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->call('cargarPermiso', $permiso->id_permiso)
        ->set('permisoForm.nombre', 'Gestionar permisos')
        ->set('permisoForm.acciones', $accionesActualizadas)
        ->call('guardar')
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->set('permisoForm.nombre', 'Gestionar   permisos')
        ->set('permisoForm.acciones', $acciones)
        ->call('guardar')
        ->assertHasErrors(['permisoForm.nombre']);
});

test('authenticated users must select at least one action for a permiso', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->set('permisoForm.nombre', 'Usuarios')
        ->set('permisoForm.acciones', [])
        ->call('guardar')
        ->assertHasErrors(['permisoForm.acciones']);
});

test('authenticated users can change estado of a permiso', function () {
    $usuario = Usuario::factory()->create();
    $permiso = Permiso::query()->create([
        'clave_permiso' => 'usuarios',
        'nombre_permiso' => 'USUARIOS',
        'estado_permiso' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->call('cambiarEstado', $permiso->id_permiso)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_permiso', [
        'id_permiso' => $permiso->id_permiso,
        'estado_permiso' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authenticated users can delete a permiso', function () {
    $usuario = Usuario::factory()->create();
    $permiso = Permiso::query()->create([
        'clave_permiso' => 'usuarios',
        'nombre_permiso' => 'USUARIOS',
        'estado_permiso' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->call('eliminar', $permiso->id_permiso)
        ->assertHasNoErrors();

    expect(Permiso::query()->find($permiso->id_permiso))->toBeNull();
    $permisoEliminado = Permiso::withTrashed()->find($permiso->id_permiso);

    expect($permisoEliminado)->not->toBeNull();
    expect($permisoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});
