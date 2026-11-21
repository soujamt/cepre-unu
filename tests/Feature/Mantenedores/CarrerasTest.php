<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

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
    crearCarreraPrueba();

    $this->actingAs($usuario)
        ->get(route('carreras.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mantenedores/carreras')
            ->has('carreras.data', 1)
            ->where('carreras.data.0.codigo', 'ENF')
            ->where('carreras.data.0.estado', 'HABILITADO'));
});

test('carreras can be searched and filtered by state', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice']);
    crearCarreraPrueba('ENF', 'ENFERMERÍA');
    crearCarreraPrueba('MED', 'MEDICINA HUMANA')->update(['estado_car' => 'DESHABILITADO']);

    $this->actingAs($usuario)
        ->get(route('carreras.index', ['buscar' => 'medic']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('carreras.data', 1)->where('carreras.data.0.codigo', 'MED'));

    $this->actingAs($usuario)
        ->get(route('carreras.index', ['estado' => ['HABILITADO']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('carreras.data', 1)
            ->where('carreras.data.0.codigo', 'ENF')
            ->where('filtros.estado', ['HABILITADO']));
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

    $this->actingAs($usuario)
        ->post(route('carreras.store'), ['codigo' => 'MED', 'nombre' => 'Medicina humana'])
        ->assertForbidden();

    expect(Carrera::query()->count())->toBe(0);
});

test('authorized users can register a carrera in uppercase', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);

    $this->actingAs($usuario)
        ->post(route('carreras.store'), ['codigo' => ' med ', 'nombre' => 'Medicina   humana'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Carrera registrada correctamente.']);

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

    $this->actingAs($usuario)
        ->post(route('carreras.store'), ['codigo' => 'enf', 'nombre' => 'administración'])
        ->assertSessionHasErrors([
            'codigo' => 'Ya existe una carrera con ese código.',
            'nombre' => 'Ya existe una carrera con ese nombre.',
        ]);

    expect(Carrera::query()->withTrashed()->count())->toBe(2);
});

test('carrera code only accepts letters, numbers and dashes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);

    $this->actingAs($usuario)
        ->post(route('carreras.store'), ['codigo' => 'MED HUM', 'nombre' => 'Medicina humana'])
        ->assertSessionHasErrors(['codigo' => 'El código solo admite letras, números, guion y guion bajo.']);
});

test('carrera requires code and name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_crear']);

    $this->actingAs($usuario)
        ->post(route('carreras.store'), ['codigo' => '  ', 'nombre' => ''])
        ->assertSessionHasErrors(['codigo' => 'El código es obligatorio.', 'nombre' => 'El nombre es obligatorio.']);
});

test('authorized users can update a carrera keeping its own code', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_editar']);
    $carrera = crearCarreraPrueba('ENF', 'ENFERMERIA');

    $this->actingAs($usuario)
        ->put(route('carreras.update', $carrera->id_car), ['codigo' => 'ENF', 'nombre' => 'Enfermería técnica'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Carrera actualizada correctamente.');

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

    $this->actingAs($usuario)
        ->patch(route('carreras.estado', $carrera->id_car))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado de la carrera actualizado.']);

    expect($carrera->fresh()->estado_car->value)->toBe('DESHABILITADO');
});

test('authorized users can delete a carrera without procesos', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarrera($usuario, ['carrera_indice', 'carrera_eliminar']);
    $carrera = crearCarreraPrueba();

    $this->actingAs($usuario)
        ->delete(route('carreras.destroy', $carrera->id_car))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Carrera eliminada correctamente.']);

    $this->assertSoftDeleted('tbl_carrera', ['id_car' => $carrera->id_car], deletedAtColumn: 'eliminado_en');
});

test('carrera enabled in a proceso cannot be deleted', function () {
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
    $proceso = Proceso::query()->create([
        'codigo_pro' => '2026_2',
        'nombre_pro' => '2026-2',
        'fecha_inicio_pro' => '2026-09-01 00:00:00',
        'fecha_fin_pro' => '2026-12-31 23:59:59',
        'estado_pro' => 'HABILITADO',
    ]);
    ProcesoCarrera::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'estado_pca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('carreras.destroy', $carrera->id_car))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', [
            'type' => 'error',
            'message' => 'La carrera se usa en la oferta de un proceso o en inscripciones y no puede eliminarse. Puede deshabilitarla.',
        ]);

    $this->assertNotSoftDeleted('tbl_carrera', ['id_car' => $carrera->id_car], deletedAtColumn: 'eliminado_en');
});
