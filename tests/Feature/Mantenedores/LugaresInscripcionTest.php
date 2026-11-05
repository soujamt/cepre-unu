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

function otorgarPermisosLugarInscripcion(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accionClave = Str::afterLast($clave, '_');
        $permisoClave = Str::beforeLast($clave, '_');

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

function crearSedeLugar(string $codigo, string $nombre): Sede
{
    $ubigeo = Ubigeo::query()->find('250101') ?? Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    return Sede::query()->create([
        'codigo_sed' => $codigo,
        'nombre_sed' => $nombre,
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);
}

function crearLugarPrueba(?Sede $sede = null, string $codigo = '603', string $nombre = 'PUCALLPA'): LugarInscripcion
{
    $sede ??= crearSedeLugar('SEDCAL', 'SEDE CALLERIA');

    return LugarInscripcion::query()->create([
        'codigo_lin' => $codigo,
        'nombre_lin' => $nombre,
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);
}

function crearModalidadLugar(string $codigo): ModalidadAdmision
{
    return ModalidadAdmision::query()->create([
        'codigo_mad' => $codigo,
        'nombre_mad' => "MODALIDAD {$codigo}",
        'estado_mad' => 'HABILITADO',
    ]);
}

function crearOfertaBaseDelLugar(ModalidadLugarInscripcion $modalidadLugar, LugarInscripcion $lugar): OfertaBase
{
    $carrera = Carrera::query()->create([
        'codigo_car' => 'ENF',
        'nombre_car' => 'ENFERMERIA',
        'estado_car' => 'HABILITADO',
    ]);
    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $lugar->id_sede,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SEDCAL-ENF',
        'estado_sca' => 'HABILITADO',
    ]);

    return OfertaBase::query()->create([
        'codigo_externo_ofb' => '2700',
        'id_modalidad_lugar_inscripcion' => $modalidadLugar->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);
}

test('authorized users can access lugares de inscripcion page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice']);

    $this->actingAs($usuario)
        ->get(route('lugares-inscripcion.index'))
        ->assertOk()
        ->assertSee('Lugares de inscripción');
});

test('authenticated users without permiso cannot access lugares de inscripcion page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('lugares-inscripcion.index'))
        ->assertForbidden();
});

test('authorized users can register a lugar de inscripcion', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_crear']);
    $sede = crearSedeLugar('SEDCAL', 'SEDE CALLERIA');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('cargarLugar')
        ->set('lugarForm.codigo', '627')
        ->set('lugarForm.nombre', 'Yarinacocha')
        ->set('lugarForm.id_sede', (string) $sede->id_sed)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Lugar de inscripción registrado correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_lugar_inscripcion', [
        'codigo_lin' => '627',
        'nombre_lin' => 'YARINACOCHA',
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);
});

test('lugar code must be unique but names can repeat', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_crear']);
    $lugar = crearLugarPrueba(codigo: '603', nombre: 'PUCALLPA');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->set('lugarForm.codigo', '603')
        ->set('lugarForm.nombre', 'Pucallpa')
        ->set('lugarForm.id_sede', (string) $lugar->id_sede)
        ->call('guardar')
        ->assertHasErrors(['lugarForm.codigo'])
        ->set('lugarForm.codigo', '604')
        ->call('guardar')
        ->assertHasNoErrors();

    expect(LugarInscripcion::query()->where('nombre_lin', 'PUCALLPA')->count())->toBe(2);
});

test('lugar sede cannot change once it has ofertas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_editar']);
    $lugar = crearLugarPrueba();
    $otraSede = crearSedeLugar('FILAGU', 'FILIAL AGUAYTIA');
    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => crearModalidadLugar('2')->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);
    crearOfertaBaseDelLugar($modalidadLugar, $lugar);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('cargarLugar', $lugar->id_lin)
        ->set('lugarForm.id_sede', (string) $otraSede->id_sed)
        ->call('guardar')
        ->assertHasErrors(['lugarForm.id_sede']);

    expect($lugar->fresh()->id_sede)->not->toBe($otraSede->id_sed);
});

test('authorized users can assign modalidades to a lugar', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_editar']);
    $lugar = crearLugarPrueba();
    $exoneracion = crearModalidadLugar('2');
    $reserva = crearModalidadLugar('8');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('cargarModalidadesLugar', $lugar->id_lin)
        ->assertSet('modalidadesSeleccionadas', [])
        ->set('modalidadesSeleccionadas', [(string) $exoneracion->id_mad, (string) $reserva->id_mad])
        ->call('guardarModalidades')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Modalidades del lugar actualizadas.', type: 'success');

    expect(ModalidadLugarInscripcion::query()
        ->where('id_lugar_inscripcion', $lugar->id_lin)
        ->where('estado_mli', 'HABILITADO')
        ->pluck('id_modalidad_admision')
        ->sort()
        ->values()
        ->all())->toBe([$exoneracion->id_mad, $reserva->id_mad]);
});

test('unchecked modalidad is removed without ofertas and disabled with ofertas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_editar']);
    $lugar = crearLugarPrueba();
    $sinOfertas = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => crearModalidadLugar('2')->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);
    $conOfertas = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => crearModalidadLugar('8')->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);
    crearOfertaBaseDelLugar($conOfertas, $lugar);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('cargarModalidadesLugar', $lugar->id_lin)
        ->set('modalidadesSeleccionadas', [])
        ->call('guardarModalidades')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('tbl_modalidad_lugar_inscripcion', ['id_mli' => $sinOfertas->id_mli], deletedAtColumn: 'eliminado_en');
    $this->assertDatabaseHas('tbl_modalidad_lugar_inscripcion', [
        'id_mli' => $conOfertas->id_mli,
        'estado_mli' => 'DESHABILITADO',
        'eliminado_en' => null,
    ]);
});

test('listing can be filtered by sede', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice']);
    $calleria = crearSedeLugar('SEDCAL', 'SEDE CALLERIA');
    $aguaytia = crearSedeLugar('FILAGU', 'FILIAL AGUAYTIA');
    crearLugarPrueba($calleria, '603', 'MANANTAY');
    crearLugarPrueba($aguaytia, '625', 'AGUAYTIA');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->set('idSede', (string) $aguaytia->id_sed)
        ->assertSee('625')
        ->assertDontSee('MANANTAY');
});

test('lugar with modalidades cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_eliminar']);
    $lugar = crearLugarPrueba();
    ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => crearModalidadLugar('2')->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('eliminar', $lugar->id_lin)
        ->assertDispatched('toast', message: 'El lugar tiene modalidades asignadas y no puede eliminarse. Puede deshabilitarlo.', type: 'error');

    $this->assertNotSoftDeleted('tbl_lugar_inscripcion', ['id_lin' => $lugar->id_lin], deletedAtColumn: 'eliminado_en');
});

test('authorized users can delete a lugar without modalidades', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosLugarInscripcion($usuario, ['lugar_inscripcion_indice', 'lugar_inscripcion_eliminar']);
    $lugar = crearLugarPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.lugares-inscripcion')
        ->call('eliminar', $lugar->id_lin)
        ->assertDispatched('toast', message: 'Lugar de inscripción eliminado correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_lugar_inscripcion', ['id_lin' => $lugar->id_lin], deletedAtColumn: 'eliminado_en');
});
