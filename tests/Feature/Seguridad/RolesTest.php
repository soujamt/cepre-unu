<?php

use App\Enums\EstadoCatalogo;
use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

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
    otorgarPermisosRol($usuario, ['rol_indice']);

    $this->actingAs($usuario)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('seguridad/roles')->has('roles.data'));
});

test('authorized users can register a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_crear']);

    $this->actingAs($usuario)
        ->post(route('roles.store'), ['nombre' => 'Coordinador', 'descripcion' => 'Gestiona procesos internos'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Rol registrado correctamente.']);

    $this->assertDatabaseHas('tbl_rol', [
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => 'Gestiona procesos internos',
        'estado_rol' => 'HABILITADO',
    ]);
});

test('authorized users can update a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_editar']);
    $rol = Rol::query()->create([
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => 'Gestiona procesos internos',
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->put(route('roles.update', $rol), ['nombre' => 'Supervisor', 'descripcion' => 'Supervisa operaciones'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Rol actualizado correctamente.');

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'nombre_rol' => 'SUPERVISOR',
        'descripcion_rol' => 'Supervisa operaciones',
    ]);
});

test('administrator role name cannot be changed', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_editar']);

    $rol = Rol::query()->create([
        'nombre_rol' => 'ADMINISTRADOR',
        'descripcion_rol' => 'Rol principal',
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->put(route('roles.update', $rol), ['nombre' => 'SUPER ADMIN', 'descripcion' => 'Rol principal actualizado'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'nombre_rol' => 'ADMINISTRADOR',
        'descripcion_rol' => 'Rol principal actualizado',
    ]);
});

test('authorized users can change state of a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_estado']);
    $rol = Rol::query()->create([
        'nombre_rol' => 'COORDINADOR',
        'descripcion_rol' => null,
        'estado_rol' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->patch(route('roles.estado', $rol))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del rol actualizado.']);

    $this->assertDatabaseHas('tbl_rol', [
        'id_rol' => $rol->id_rol,
        'estado_rol' => 'DESHABILITADO',
    ]);
});

test('authorized users can assign permisos to a role', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_permisos']);
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

    $this->actingAs($usuario)
        ->get(route('roles.permisos', $rol))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('seguridad/roles-permisos')
            ->where('rol.nombre', 'COORDINADOR')
            ->where('seleccionadas', [])
            ->where('permisos', fn (Collection $permisos): bool => $permisos->contains(
                fn (array $modulo): bool => $modulo['clave'] === 'usuarios' && count($modulo['acciones']) === 2,
            )));

    $this->actingAs($usuario)
        ->put(route('roles.permisos.update', $rol), ['seleccionadas' => $permisoAcciones->pluck('id_peracc')->all()])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Permisos del rol actualizados correctamente.']);

    foreach ($permisoAcciones as $permisoAccion) {
        $this->assertDatabaseHas('tbl_rol_permiso_accion', [
            'id_rol' => $rol->id_rol,
            'id_peracc' => $permisoAccion->id_peracc,
        ]);
    }
});

test('roles index counts only active assigned actions', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice']);

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

    $this->actingAs($usuario)
        ->get(route('roles.index', ['buscar' => 'EDITOR']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('roles.data', 1)
            ->where('roles.data.0.acciones', 1));
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

test('removing permisos from a role soft deletes the unchecked actions', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_permisos']);
    $rol = Rol::query()->create(['nombre_rol' => 'COORDINADOR', 'estado_rol' => 'HABILITADO']);
    $permiso = Permiso::query()->create(['nombre_permiso' => 'USUARIOS', 'clave_permiso' => 'usuarios', 'estado_permiso' => 'HABILITADO']);
    $accion = Accion::query()->where('clave_accion', 'indice')->firstOrFail();
    $permisoAccion = PermisoAccion::query()->create([
        'id_permiso' => $permiso->id_permiso,
        'id_accion' => $accion->id_acc,
        'clave_peracc' => 'usuarios_indice',
        'estado_peracc' => 'HABILITADO',
    ]);
    $rol->rolPermisoAcciones()->create(['id_peracc' => $permisoAccion->id_peracc]);

    $this->actingAs($usuario)
        ->put(route('roles.permisos.update', $rol), ['seleccionadas' => []])
        ->assertSessionHasNoErrors();

    $this->assertSoftDeleted('tbl_rol_permiso_accion', ['id_rol' => $rol->id_rol, 'id_peracc' => $permisoAccion->id_peracc], deletedAtColumn: 'eliminado_en');
});

test('administrator role cannot be disabled, deleted or reassigned', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRol($usuario, ['rol_indice', 'rol_estado', 'rol_eliminar', 'rol_permisos']);
    $rol = Rol::query()->firstOrCreate(['nombre_rol' => 'ADMINISTRADOR'], ['estado_rol' => 'HABILITADO']);

    $this->actingAs($usuario)
        ->patch(route('roles.estado', $rol))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'error', 'message' => 'El rol ADMINISTRADOR no puede cambiar de estado.']);

    $this->actingAs($usuario)
        ->delete(route('roles.destroy', $rol))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'error', 'message' => 'El rol ADMINISTRADOR no puede eliminarse.']);

    $this->actingAs($usuario)
        ->put(route('roles.permisos.update', $rol), ['seleccionadas' => []])
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'info', 'message' => 'El rol ADMINISTRADOR ya cuenta con acceso total.']);

    expect($rol->fresh()->estado_rol->value)->toBe('HABILITADO')
        ->and($rol->fresh()->trashed())->toBeFalse();
});
