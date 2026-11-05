<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\OfertaBase;
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

function crearOfertaBaseParaSedeCarrera(SedeCarrera $sedeCarrera): OfertaBase
{
    $lugar = LugarInscripcion::query()->create([
        'codigo_lin' => 'LUGAR1',
        'nombre_lin' => 'LUGAR 1',
        'id_sede' => $sedeCarrera->id_sede,
        'estado_lin' => 'HABILITADO',
    ]);
    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => 'MAD1',
        'nombre_mad' => 'MODALIDAD 1',
        'estado_mad' => 'HABILITADO',
    ]);
    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    return OfertaBase::query()->create([
        'codigo_externo_ofb' => '2700',
        'id_modalidad_lugar_inscripcion' => $modalidadLugar->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);
}

test('authorized users can access sedes page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice']);

    $this->actingAs($usuario)
        ->get(route('sedes.index'))
        ->assertOk()
        ->assertSee('Sedes');
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

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarSede')
        ->set('sedeForm.codigo', 'filagu')
        ->set('sedeForm.nombre', 'Filial Aguaytía')
        ->set('sedeForm.id_ubigeo', '250101')
        ->set('sedeForm.direccion', 'Jr. Los Pinos 123')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Sede registrada correctamente.', type: 'success');

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

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->set('sedeForm.codigo', 'sedcal')
        ->set('sedeForm.nombre', 'Otra sede')
        ->set('sedeForm.id_ubigeo', '999999')
        ->call('guardar')
        ->assertHasErrors(['sedeForm.codigo', 'sedeForm.id_ubigeo' => 'exists']);
});

test('authorized users can update a sede', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_editar']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarSede', $sede->id_sed)
        ->assertSet('sedeForm.id_ubigeo', '250101')
        ->set('sedeForm.nombre', 'Sede central Callería')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_sede', [
        'id_sed' => $sede->id_sed,
        'codigo_sed' => 'SEDCAL',
        'nombre_sed' => 'SEDE CENTRAL CALLERÍA',
    ]);
});

test('authorized users can assign carreras to a sede', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_editar']);
    $sede = crearSedePrueba();
    $enfermeria = crearCarreraSede('ENF');
    $medicina = crearCarreraSede('MED');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarCarrerasSede', $sede->id_sed)
        ->assertSet('carrerasSeleccionadas', [])
        ->set('carrerasSeleccionadas', [(string) $enfermeria->id_car, (string) $medicina->id_car])
        ->call('guardarCarreras')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Carreras de la sede actualizadas.', type: 'success');

    $this->assertDatabaseHas('tbl_sede_carrera', [
        'id_sede' => $sede->id_sed,
        'id_carrera' => $enfermeria->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);
    $this->assertDatabaseHas('tbl_sede_carrera', [
        'id_sede' => $sede->id_sed,
        'id_carrera' => $medicina->id_car,
        'codigo_externo_sca' => 'SEDCAL-MED',
    ]);
});

test('unchecked carrera is removed from the sede when it has no ofertas and restored when checked again', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_editar']);
    $sede = crearSedePrueba();
    $carrera = crearCarreraSede('ENF');
    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarCarrerasSede', $sede->id_sed)
        ->assertSet('carrerasSeleccionadas', [(string) $carrera->id_car])
        ->set('carrerasSeleccionadas', [])
        ->call('guardarCarreras')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('tbl_sede_carrera', ['id_sca' => $sedeCarrera->id_sca], deletedAtColumn: 'eliminado_en');

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarCarrerasSede', $sede->id_sed)
        ->set('carrerasSeleccionadas', [(string) $carrera->id_car])
        ->call('guardarCarreras')
        ->assertHasNoErrors();

    $this->assertNotSoftDeleted('tbl_sede_carrera', ['id_sca' => $sedeCarrera->id_sca], deletedAtColumn: 'eliminado_en');
    expect(SedeCarrera::query()->withTrashed()->count())->toBe(1);
});

test('unchecked carrera with ofertas is disabled instead of removed', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_editar']);
    $sede = crearSedePrueba();
    $carrera = crearCarreraSede('ENF');
    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);
    crearOfertaBaseParaSedeCarrera($sedeCarrera);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarCarrerasSede', $sede->id_sed)
        ->set('carrerasSeleccionadas', [])
        ->call('guardarCarreras')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_sede_carrera', [
        'id_sca' => $sedeCarrera->id_sca,
        'estado_sca' => 'DESHABILITADO',
        'eliminado_en' => null,
    ]);
});

test('users without editar permiso cannot assign carreras', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cargarCarrerasSede', $sede->id_sed)
        ->assertForbidden();
});

test('authorized users can change state of a sede', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_estado']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('cambiarEstado', $sede->id_sed)
        ->assertDispatched('toast', message: 'Estado de la sede actualizado.', type: 'success');

    expect($sede->fresh()->estado_sed->value)->toBe('DESHABILITADO');
});

test('authorized users can delete a sede without carreras or lugares', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_eliminar']);
    $sede = crearSedePrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('eliminar', $sede->id_sed)
        ->assertDispatched('toast', message: 'Sede eliminada correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_sede', ['id_sed' => $sede->id_sed], deletedAtColumn: 'eliminado_en');
});

test('sede with carreras cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSede($usuario, ['sede_indice', 'sede_eliminar']);
    $sede = crearSedePrueba();
    SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => crearCarreraSede('ENF')->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.sedes')
        ->call('eliminar', $sede->id_sed)
        ->assertDispatched('toast', message: 'La sede tiene carreras asignadas y no puede eliminarse. Puede deshabilitarla.', type: 'error');

    $this->assertNotSoftDeleted('tbl_sede', ['id_sed' => $sede->id_sed], deletedAtColumn: 'eliminado_en');
});
