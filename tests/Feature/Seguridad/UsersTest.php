<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosUsuario(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'GESTOR_'.str()->upper(str()->random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->usuarioRoles()->create([
        'id_rol' => $rol->id_rol,
    ]);

    foreach ($claves as $clave) {
        [$permisoClave, $accionClave] = explode('_', $clave, 2);

        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $permisoClave],
            [
                'nombre_permiso' => str()->upper(str_replace('_', ' ', $permisoClave)),
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

        $rol->rolPermisoAcciones()->updateOrCreate([
            'id_peracc' => $permisoAccion->id_peracc,
        ]);
    }
}

test('authorized users can access the users page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice']);

    $this->actingAs($usuario)
        ->get(route('usuarios.index'))
        ->assertOk()
        ->assertSee('USUARIOS');
});

test('authorized users can register a user with roles from the modal', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_crear']);

    $roles = collect([
        Rol::query()->create([
            'nombre_rol' => 'OPERADOR',
            'descripcion_rol' => 'Rol operativo',
            'estado_rol' => 'HABILITADO',
        ]),
        Rol::query()->create([
            'nombre_rol' => 'CONSULTOR',
            'descripcion_rol' => 'Rol de consulta',
            'estado_rol' => 'HABILITADO',
        ]),
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->set('usuarioForm.correo', 'analista@unu.edu.pe')
        ->set('usuarioForm.nombres', 'Usuario Analista')
        ->set('usuarioForm.password', 'secret123')
        ->set('usuarioForm.roles', $roles->pluck('id_rol')->map(fn (int $id): string => (string) $id)->all())
        ->call('guardar')
        ->assertHasNoErrors();

    $usuarioRegistrado = Usuario::query()->where('correo_usu', 'analista@unu.edu.pe')->first();

    expect($usuarioRegistrado)->not->toBeNull();
    expect($usuarioRegistrado?->usuario_usu)->toBe('analista');
    expect($usuarioRegistrado?->nombres_usu)->toBe('USUARIO ANALISTA');

    foreach ($roles as $rol) {
        $this->assertDatabaseHas('tbl_usuario_rol', [
            'id_usuario' => $usuarioRegistrado->id_usu,
            'id_rol' => $rol->id_rol,
        ]);
    }
});

test('authorized users can update a user and sync roles', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_editar']);

    $objetivo = Usuario::factory()->create([
        'usuario_usu' => 'coordinador',
        'correo_usu' => 'coordinador@unu.edu.pe',
        'nombres_usu' => 'Usuario Coordinador',
    ]);

    $rolAnterior = Rol::query()->create([
        'nombre_rol' => 'AUXILIAR',
        'descripcion_rol' => 'Rol auxiliar',
        'estado_rol' => 'HABILITADO',
    ]);

    $rolNuevo = Rol::query()->create([
        'nombre_rol' => 'SUPERVISOR',
        'descripcion_rol' => 'Rol supervisor',
        'estado_rol' => 'HABILITADO',
    ]);

    $objetivo->usuarioRoles()->create([
        'id_rol' => $rolAnterior->id_rol,
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->call('cargarUsuario', $objetivo->id_usu)
        ->set('usuarioForm.correo', 'supervisor@unu.edu.pe')
        ->set('usuarioForm.nombres', 'Usuario Supervisor')
        ->set('usuarioForm.password', '')
        ->set('usuarioForm.roles', [(string) $rolNuevo->id_rol])
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_usuario', [
        'id_usu' => $objetivo->id_usu,
        'correo_usu' => 'supervisor@unu.edu.pe',
        'nombres_usu' => 'USUARIO SUPERVISOR',
    ]);

    $this->assertDatabaseHas('tbl_usuario_rol', [
        'id_usuario' => $objetivo->id_usu,
        'id_rol' => $rolNuevo->id_rol,
        'eliminado_en' => null,
    ]);

    $this->assertDatabaseHas('tbl_usuario_rol', [
        'id_usuario' => $objetivo->id_usu,
        'id_rol' => $rolAnterior->id_rol,
    ]);
});

test('authorized users can change state of a user', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_estado']);

    $objetivo = Usuario::factory()->create([
        'estado_usu' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->call('cambiarEstado', $objetivo->id_usu)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_usuario', [
        'id_usu' => $objetivo->id_usu,
        'estado_usu' => 'DESHABILITADO',
    ]);
});

test('authorized users can delete a user', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_eliminar']);

    $objetivo = Usuario::factory()->create();

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->call('eliminar', $objetivo->id_usu)
        ->assertHasNoErrors();

    $this->assertSoftDeleted($objetivo->fresh());
});

test('users cannot disable their own account from the module', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_estado']);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->call('cambiarEstado', $usuario->id_usu);

    $this->assertDatabaseHas('tbl_usuario', [
        'id_usu' => $usuario->id_usu,
        'estado_usu' => 'HABILITADO',
    ]);
});

test('generated username falls back to a unique value when email prefix already exists', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosUsuario($usuario, ['usuario_indice', 'usuario_crear']);

    Usuario::factory()->create([
        'usuario_usu' => 'analista',
        'correo_usu' => 'analista.existente@unu.edu.pe',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.usuarios')
        ->set('usuarioForm.correo', 'analista@unu.edu.pe')
        ->set('usuarioForm.nombres', 'Analista Nuevo')
        ->set('usuarioForm.password', 'secret123')
        ->set('usuarioForm.roles', [])
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_usuario', [
        'correo_usu' => 'analista@unu.edu.pe',
        'usuario_usu' => 'analista1',
    ]);
});

test('user assigned a role inherits sidebar permission access', function () {
    $administrador = Usuario::factory()->create();
    otorgarPermisosUsuario($administrador, ['usuario_indice', 'usuario_crear']);

    $rol = Rol::query()->create([
        'nombre_rol' => 'CONSULTOR',
        'descripcion_rol' => 'Rol de consulta',
        'estado_rol' => 'HABILITADO',
    ]);

    $permiso = Permiso::query()->firstOrCreate(
        ['clave_permiso' => 'permisos'],
        [
            'nombre_permiso' => 'PERMISOS',
            'estado_permiso' => 'HABILITADO',
        ],
    );

    $accion = Accion::query()->where('clave_accion', 'indice')->firstOrFail();

    $permisoAccion = PermisoAccion::query()->updateOrCreate(
        [
            'id_permiso' => $permiso->id_permiso,
            'id_accion' => $accion->id_acc,
        ],
        [
            'clave_peracc' => 'permiso_indice',
            'estado_peracc' => 'HABILITADO',
            'eliminado_en' => null,
        ],
    );

    $rol->rolPermisoAcciones()->create([
        'id_peracc' => $permisoAccion->id_peracc,
    ]);

    $this->actingAs($administrador);

    Livewire::test('pages::seguridad.usuarios')
        ->set('usuarioForm.correo', 'permisos@unu.edu.pe')
        ->set('usuarioForm.nombres', 'Usuario Permisos')
        ->set('usuarioForm.password', 'secret123')
        ->set('usuarioForm.roles', [(string) $rol->id_rol])
        ->call('guardar')
        ->assertHasNoErrors();

    $usuario = Usuario::query()->where('correo_usu', 'permisos@unu.edu.pe')->firstOrFail();

    expect($usuario->hasPermisoAccion('permiso_indice'))->toBeTrue();
});
