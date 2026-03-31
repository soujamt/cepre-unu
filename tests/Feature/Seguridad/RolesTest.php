<?php

use App\Enums\EstadoCatalogo;
use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosRol(Usuario $usuario, array $claves): void
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

test('authorized users can access the roles page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice']);

    $this->actingAs($usuario)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSee('ROLES');
});

test('authorized users can register a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice', 'roles_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.roles')
        ->set('rolForm.nombre', 'Coordinador')
        ->set('rolForm.descripcion', 'Gestiona procesos internos')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_rol', [
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => 'Gestiona procesos internos',
        'estado_rol' => 'HABILITADO',
    ]);
});

test('authorized users can update a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice', 'roles_editar']);
    $rol = Rol::query()->create([
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => 'Gestiona procesos internos',
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.roles')
        ->call('cargarRol', $rol->id_rol)
        ->set('rolForm.nombre', 'Supervisor')
        ->set('rolForm.descripcion', 'Supervisa operaciones')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'nombre_rol' => 'SUPERVISOR',
        'descripcion_rol' => 'Supervisa operaciones',
    ]);
});

test('administrator role name cannot be changed', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice', 'roles_editar']);

    $rol = Rol::query()->create([
        'nombre_rol' => 'ADMINISTRADOR',
        'descripcion_rol' => 'Rol principal',
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.roles')
        ->call('cargarRol', $rol->id_rol)
        ->set('rolForm.nombre', 'SUPER ADMIN')
        ->set('rolForm.descripcion', 'Rol principal actualizado')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'nombre_rol' => 'ADMINISTRADOR',
        'descripcion_rol' => 'Rol principal actualizado',
    ]);
});

test('authorized users can change state of a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice', 'roles_estado']);
    $rol = Rol::query()->create([
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => null,
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.roles')
        ->call('cambiarEstado', $rol->id_rol)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'estado_rol' => 'DESHABILITADO',
    ]);
});

test('authorized users can assign permisos to a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice', 'roles_permisos']);
    $rol = Rol::query()->create([
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => null,
        'estado_rol' => 'HABILITADO',
    ]);
    $permiso = Permiso::query()->create([
        'nombre_permiso' => 'USUARIOS',
        'clave_permiso' => 'usuarios',
        'estado_permiso' => 'HABILITADO',
    ]);
    $acciones = Accion::query()->whereIn('clave_accion', ['indice', 'crear'])->get();

    $permisoAcciones = $acciones->map(function (Accion $accion) use ($permiso): PermisoAccion {
        return PermisoAccion::query()->create([
            'id_permiso' => $permiso->id_permiso,
            'id_accion' => $accion->id_acc,
            'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
            'estado_peracc' => 'HABILITADO',
        ]);
    });

    $this->actingAs($usuario);

    Livewire::test('pages::seguridad.roles-permisos', ['rol' => $rol->id_rol])
        ->set('seleccionadas', $permisoAcciones->pluck('id_peracc')->map(fn (int $id): string => (string) $id)->all())
        ->call('guardar')
        ->assertHasNoErrors();

    foreach ($permisoAcciones as $permisoAccion) {
        $this->assertDatabaseHas('tbl_rol_permiso_accion', [
            'id_rol' => $rol->id_rol,
            'id_peracc' => $permisoAccion->id_peracc,
        ]);
    }
});

test('roles index counts only active assigned actions', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['roles_indice']);

    $rol = Rol::query()->create([
        'nombre_rol' => 'EDITOR',
        'descripcion_rol' => 'Rol con permisos parciales',
        'estado_rol' => 'HABILITADO',
    ]);

    $permiso = Permiso::query()->create([
        'nombre_permiso' => 'REPORTES',
        'clave_permiso' => 'reportes',
        'estado_permiso' => 'HABILITADO',
    ]);

    $acciones = Accion::query()->whereIn('clave_accion', ['indice', 'crear'])->get();

    $permisoAcciones = $acciones->map(function (Accion $accion) use ($permiso): PermisoAccion {
        return PermisoAccion::query()->create([
            'id_permiso' => $permiso->id_permiso,
            'id_accion' => $accion->id_acc,
            'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
            'estado_peracc' => 'HABILITADO',
        ]);
    });

    $rol->rolPermisoAcciones()->create([
        'id_peracc' => $permisoAcciones[0]->id_peracc,
    ]);

    $rol->rolPermisoAcciones()->create([
        'id_peracc' => $permisoAcciones[1]->id_peracc,
    ])->delete();

    $this->actingAs($usuario);

    $component = Livewire::test('pages::seguridad.roles');
    $roles = $component->instance()->roles;

    expect($roles->firstWhere('id_rol', $rol->id_rol)?->acciones_asignadas_count)->toBe(1);
});

test('soft deleted role assignments no longer grant access', function () {
    $usuario = Usuario::factory()->create();
    $rol = Rol::query()->create([
        'nombre_rol' => 'ANALISTA',
        'descripcion_rol' => 'Rol para validaciones',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    $permiso = Permiso::query()->create([
        'nombre_permiso' => 'REPORTES',
        'clave_permiso' => 'reportes',
        'estado_permiso' => 'HABILITADO',
    ]);

    $accion = Accion::query()->where('clave_accion', 'indice')->firstOrFail();

    $permisoAccion = PermisoAccion::query()->create([
        'id_permiso' => $permiso->id_permiso,
        'id_accion' => $accion->id_acc,
        'clave_peracc' => 'reportes_indice',
        'estado_peracc' => 'HABILITADO',
    ]);

    $rol->rolPermisoAcciones()->create([
        'id_peracc' => $permisoAccion->id_peracc,
    ])->delete();

    expect($usuario->hasPermisoAccion('reportes_indice'))->toBeFalse();
});

test('disabled actions no longer grant access to non administrator roles', function () {
    $usuario = Usuario::factory()->create();
    $rol = Rol::query()->create([
        'nombre_rol' => 'CONSULTA',
        'descripcion_rol' => 'Rol para consultas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    $permiso = Permiso::query()->create([
        'nombre_permiso' => 'INSCRIPCIONES',
        'clave_permiso' => 'inscripciones',
        'estado_permiso' => 'HABILITADO',
    ]);

    $accion = Accion::query()->where('clave_accion', 'indice')->firstOrFail();
    $accion->update([
        'estado_accion' => EstadoCatalogo::Deshabilitado->value,
    ]);

    $permisoAccion = PermisoAccion::query()->create([
        'id_permiso' => $permiso->id_permiso,
        'id_accion' => $accion->id_acc,
        'clave_peracc' => 'inscripciones_indice',
        'estado_peracc' => 'HABILITADO',
    ]);

    $rol->rolPermisoAcciones()->create([
        'id_peracc' => $permisoAccion->id_peracc,
    ]);

    expect($usuario->hasPermisoAccion('inscripciones_indice'))->toBeFalse();
});
