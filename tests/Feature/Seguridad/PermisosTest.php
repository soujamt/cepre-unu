<?php

use App\Models\Accion;
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

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->set('permisoForm.nombre', 'Ver permisos')
        ->set('permisoForm.descripcion', 'Permite visualizar el listado de permisos.')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_accion', [
        'codigo_acc' => 'VER_PERMISOS',
        'nombre_acc' => 'Ver permisos',
        'modulo_acc' => 'ver-permisos',
        'estado_acc' => 'HABILITADO',
    ]);
});

test('authenticated users can update a permiso', function () {
    $usuario = Usuario::factory()->create();
    $permiso = Accion::query()->create([
        'codigo_acc' => 'VER_PERMISOS',
        'nombre_acc' => 'Ver permisos',
        'modulo_acc' => 'ver-permisos',
        'descripcion_acc' => 'Inicial',
        'estado_acc' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->call('cargarPermiso', $permiso->id_acc)
        ->set('permisoForm.nombre', 'Gestionar permisos')
        ->set('permisoForm.descripcion', 'Actualizado')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_accion', [
        'id_acc' => $permiso->id_acc,
        'codigo_acc' => 'GESTIONAR_PERMISOS',
        'nombre_acc' => 'Gestionar permisos',
        'modulo_acc' => 'gestionar-permisos',
        'descripcion_acc' => 'Actualizado',
    ]);
});

test('authenticated users cannot register duplicated permisos by generated code', function () {
    $usuario = Usuario::factory()->create();

    Accion::query()->create([
        'codigo_acc' => 'GESTIONAR_PERMISOS',
        'nombre_acc' => 'Gestionar permisos',
        'modulo_acc' => 'gestionar-permisos',
        'descripcion_acc' => 'Inicial',
        'estado_acc' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.permisos')
        ->set('permisoForm.nombre', 'Gestionar   permisos')
        ->set('permisoForm.descripcion', 'Duplicado')
        ->call('guardar')
        ->assertHasErrors(['permisoForm.nombre']);
});
