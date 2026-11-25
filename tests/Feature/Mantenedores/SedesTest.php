<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\ModalidadAdmision;
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

function otorgarPermisosSede(Usuario $usuario, array $claves): void
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

function crearUbigeoSede(): Ubigeo
{
    return Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);
}

function crearSedePrueba(string $codigo = 'SEDCAL', string $nombre = 'SEDE CALLERIA'): Sede
{
    $ubigeo = Ubigeo::query()->find('250101') ?? crearUbigeoSede();

    return Sede::query()->create([
        'codigo_sed' => $codigo,
        'nombre_sed' => $nombre,
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);
}

function crearCarreraSede(string $codigo, string $estado = 'HABILITADO'): Carrera
{
    return Carrera::query()->create([
        'codigo_car' => $codigo,
        'nombre_car' => "CARRERA {$codigo}",
        'estado_car' => $estado,
    ]);
}

test('authorized users can access sedes page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice']);
    crearSedePrueba();

    $this->actingAs($usuario)
        ->get(route('sedes.index', ['buscar' => 'sedcal']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mantenedores/sedes')
            ->has('sedes.data', 1)
            ->where('sedes.data.0.codigo', 'SEDCAL')
            ->where('sedes.data.0.ubigeo.codigo', '250101')
            ->where('sedes.data.0.ubigeo.etiqueta', '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA')
            ->where('sedes.data.0.inscritos', 0));
});

test('authenticated users without permiso cannot access sedes page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('sedes.index'))
        ->assertForbidden();
});

test('authorized users can register a sede with its ubigeo', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_crear']);
    crearUbigeoSede();

    $this->actingAs($usuario)
        ->post(route('sedes.store'), [
            'codigo' => 'filagu',
            'nombre' => 'Filial Aguaytía',
            'id_ubigeo' => '250101',
            'direccion' => 'Jr. Los Pinos 123',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Sede registrada correctamente.']);

    $this->assertDatabaseHas('tbl_sede', [
        'codigo_sed' => 'FILAGU',
        'nombre_sed' => 'FILIAL AGUAYTÍA',
        'id_ubigeo' => '250101',
        'direccion_sed' => 'JR. LOS PINOS 123',
        'estado_sed' => 'HABILITADO',
    ]);
});

test('sede requires a valid ubigeo and a unique code', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_crear']);
    crearSedePrueba('SEDCAL', 'SEDE CALLERIA');

    $this->actingAs($usuario)
        ->post(route('sedes.store'), ['codigo' => 'sedcal', 'nombre' => 'Otra sede', 'id_ubigeo' => '999999'])
        ->assertSessionHasErrors([
            'codigo' => 'Ya existe una sede con ese código.',
            'id_ubigeo' => 'Seleccione un ubigeo válido.',
        ]);
});

test('authorized users can update a sede without address', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_editar']);
    $sede = crearSedePrueba();
    $sede->update(['direccion_sed' => 'JR. LOS PINOS 123']);

    $this->actingAs($usuario)
        ->put(route('sedes.update', $sede), ['codigo' => 'SEDCAL', 'nombre' => 'Sede central Callería', 'id_ubigeo' => '250101', 'direccion' => '  '])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Sede actualizada correctamente.');

    $this->assertDatabaseHas('tbl_sede', [
        'id_sed' => $sede->id_sed,
        'codigo_sed' => 'SEDCAL',
        'nombre_sed' => 'SEDE CENTRAL CALLERÍA',
        'direccion_sed' => null,
    ]);
});

test('authorized users can change state of a sede', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_estado']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario)
        ->patch(route('sedes.estado', $sede))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado de la sede actualizado.']);

    expect($sede->fresh()->estado_sed->value)->toBe('DESHABILITADO');
});

test('authorized users can delete a sede without carreras or lugares', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_eliminar']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario)
        ->delete(route('sedes.destroy', $sede))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Sede eliminada correctamente.']);

    $this->assertSoftDeleted('tbl_sede', ['id_sed' => $sede->id_sed], deletedAtColumn: 'eliminado_en');
});

test('sede enabled in a proceso cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_eliminar']);
    $sede = crearSedePrueba();
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
        'id_modalidad_admision' => ModalidadAdmision::query()->where('codigo_mad', 'ORD')->value('id_mad'),
        'id_carrera' => crearCarreraSede('ENF')->id_car,
        'estado_pca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('sedes.destroy', $sede))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', [
            'type' => 'error',
            'message' => 'La sede se usa en la oferta de un proceso o en inscripciones y no puede eliminarse. Puede deshabilitarla.',
        ]);

    $this->assertNotSoftDeleted('tbl_sede', ['id_sed' => $sede->id_sed], deletedAtColumn: 'eliminado_en');
});
