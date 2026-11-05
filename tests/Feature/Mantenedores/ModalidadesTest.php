<?php

use App\Models\Accion;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosModalidad(Usuario $usuario, array $claves): void
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

function crearModalidadPrueba(string $codigo = '2', string $nombre = 'EXONERACIÓN - CEPREUNU'): ModalidadAdmision
{
    return ModalidadAdmision::query()->create([
        'codigo_mad' => $codigo,
        'nombre_mad' => $nombre,
        'estado_mad' => 'HABILITADO',
    ]);
}

test('authorized users can access modalidades page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice']);

    $this->actingAs($usuario)
        ->get(route('modalidades.index'))
        ->assertOk()
        ->assertSee('Modalidades de admisión');
});

test('authenticated users without permiso cannot access modalidades page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('modalidades.index'))
        ->assertForbidden();
});

test('authorized users can register a modalidad with description', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->call('cargarModalidad')
        ->set('modalidadForm.codigo', ' 9 ')
        ->set('modalidadForm.nombre', 'Primera opción - Cepreunu')
        ->set('modalidadForm.descripcion', '  Para los mejores puestos  ')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Modalidad registrada correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_modalidad_admision', [
        'codigo_mad' => '9',
        'nombre_mad' => 'PRIMERA OPCIÓN - CEPREUNU',
        'descripcion_mad' => 'Para los mejores puestos',
        'estado_mad' => 'HABILITADO',
    ]);
});

test('modalidad code and name must be unique including deleted modalidades', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_crear']);
    crearModalidadPrueba('2', 'EXONERACIÓN - CEPREUNU');
    crearModalidadPrueba('8', 'RESERVA - CEPREUNU')->delete();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->set('modalidadForm.codigo', '2')
        ->set('modalidadForm.nombre', 'reserva - cepreunu')
        ->call('guardar')
        ->assertHasErrors(['modalidadForm.codigo', 'modalidadForm.nombre']);
});

test('authorized users can update a modalidad', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_editar']);
    $modalidad = crearModalidadPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->call('cargarModalidad', $modalidad->id_mad)
        ->assertSet('modalidadForm.codigo', '2')
        ->set('modalidadForm.nombre', 'Exoneración')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_modalidad_admision', [
        'id_mad' => $modalidad->id_mad,
        'nombre_mad' => 'EXONERACIÓN',
        'descripcion_mad' => null,
    ]);
});

test('authorized users can change state of a modalidad', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_estado']);
    $modalidad = crearModalidadPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->call('cambiarEstado', $modalidad->id_mad)
        ->assertDispatched('toast', message: 'Estado de la modalidad actualizado.', type: 'success');

    expect($modalidad->fresh()->estado_mad->value)->toBe('DESHABILITADO');
});

test('authorized users can delete a modalidad without lugares', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_eliminar']);
    $modalidad = crearModalidadPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->call('eliminar', $modalidad->id_mad)
        ->assertDispatched('toast', message: 'Modalidad eliminada correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_modalidad_admision', ['id_mad' => $modalidad->id_mad], deletedAtColumn: 'eliminado_en');
});

test('modalidad assigned to a lugar cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosModalidad($usuario, ['modalidad_indice', 'modalidad_eliminar']);
    $modalidad = crearModalidadPrueba();

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
    $lugar = LugarInscripcion::query()->create([
        'codigo_lin' => '603',
        'nombre_lin' => 'PUCALLPA',
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);
    ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.modalidades')
        ->call('eliminar', $modalidad->id_mad)
        ->assertDispatched('toast', message: 'La modalidad está asignada a lugares de inscripción y no puede eliminarse. Puede deshabilitarla.', type: 'error');

    $this->assertNotSoftDeleted('tbl_modalidad_admision', ['id_mad' => $modalidad->id_mad], deletedAtColumn: 'eliminado_en');
});
