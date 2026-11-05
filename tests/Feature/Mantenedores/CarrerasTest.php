<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosCarrera(Usuario $usuario, array $claves): void
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

function crearCarreraPrueba(string $codigo = 'ENF', string $nombre = 'ENFERMERÍA'): Carrera
{
    return Carrera::query()->create([
        'codigo_car' => $codigo,
        'nombre_car' => $nombre,
        'estado_car' => 'HABILITADO',
    ]);
}

test('authorized users can access carreras page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice']);

    $this->actingAs($usuario)
        ->get(route('carreras.index'))
        ->assertOk()
        ->assertSee('Carreras');
});

test('authenticated users without permiso cannot access carreras page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('carreras.index'))
        ->assertForbidden();
});

test('users without crear permiso cannot register a carrera', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->set('carreraForm.codigo', 'MED')
        ->set('carreraForm.nombre', 'Medicina humana')
        ->call('guardar')
        ->assertForbidden();

    expect(Carrera::query()->count())->toBe(0);
});

test('authorized users can register a carrera in uppercase', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->call('cargarCarrera')
        ->set('carreraForm.codigo', ' med ')
        ->set('carreraForm.nombre', 'Medicina   humana')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Carrera registrada correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_carrera', [
        'codigo_car' => 'MED',
        'nombre_car' => 'MEDICINA HUMANA',
        'estado_car' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
    ]);
});

test('carrera code and name must be unique including deleted carreras', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);
    crearCarreraPrueba('ENF', 'ENFERMERÍA');
    crearCarreraPrueba('ADM', 'ADMINISTRACIÓN')->delete();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->set('carreraForm.codigo', 'enf')
        ->set('carreraForm.nombre', 'administración')
        ->call('guardar')
        ->assertHasErrors(['carreraForm.codigo', 'carreraForm.nombre']);

    expect(Carrera::query()->withTrashed()->count())->toBe(2);
});

test('carrera code only accepts letters, numbers and dashes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->set('carreraForm.codigo', 'MED HUM')
        ->set('carreraForm.nombre', 'Medicina humana')
        ->call('guardar')
        ->assertHasErrors(['carreraForm.codigo' => 'regex']);
});

test('authorized users can update a carrera keeping its own code', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_editar']);
    $carrera = crearCarreraPrueba('ENF', 'ENFERMERIA');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->call('cargarCarrera', $carrera->id_car)
        ->assertSet('carreraForm.codigo', 'ENF')
        ->set('carreraForm.nombre', 'Enfermería técnica')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_carrera', [
        'id_car' => $carrera->id_car,
        'codigo_car' => 'ENF',
        'nombre_car' => 'ENFERMERÍA TÉCNICA',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a carrera', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_estado']);
    $carrera = crearCarreraPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->call('cambiarEstado', $carrera->id_car)
        ->assertDispatched('toast', message: 'Estado de la carrera actualizado.', type: 'success');

    expect($carrera->fresh()->estado_car->value)->toBe('DESHABILITADO');
});

test('authorized users can delete a carrera without sedes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_eliminar']);
    $carrera = crearCarreraPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->call('eliminar', $carrera->id_car)
        ->assertDispatched('toast', message: 'Carrera eliminada correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_carrera', ['id_car' => $carrera->id_car], deletedAtColumn: 'eliminado_en');
});

test('carrera assigned to a sede cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_eliminar']);
    $carrera = crearCarreraPrueba();

    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);
    $sede = Sede::query()->create([
        'codigo_sed' => 'SEDCAL',
        'nombre_sed' => 'SEDE CALLERIA',
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);
    SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.carreras')
        ->call('eliminar', $carrera->id_car)
        ->assertDispatched('toast', message: 'La carrera está asignada a una sede y no puede eliminarse. Puede deshabilitarla.', type: 'error');

    $this->assertNotSoftDeleted('tbl_carrera', ['id_car' => $carrera->id_car], deletedAtColumn: 'eliminado_en');
});
