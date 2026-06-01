<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\Colegio;
use App\Models\EstadoCivil;
use App\Models\Inscripcion;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\Nacionalidad;
use App\Models\OfertaBase;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Semestre;
use App\Models\TipoColegio;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosColegio(Usuario $usuario, array $claves): void
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

test('authorized users can access colegios page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice']);

    $this->actingAs($usuario)
        ->get(route('mantenedor-colegios.index'))
        ->assertOk()
        ->assertSee('COLEGIOS');
});

test('authenticated users without permiso cannot access colegios page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('mantenedor-colegios.index'))
        ->assertForbidden();
});

test('authorized users can register a colegio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_crear']);

    $tipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PUB',
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ]);

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

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->set('colegioForm.id', '0915389')
        ->set('colegioForm.codigo_modular', '0915389')
        ->set('colegioForm.nombre', 'Agropecuario Padre Abad')
        ->set('colegioForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('colegioForm.id_ubigeo', $ubigeo->id_ubi)
        ->set('colegioForm.direccion', 'Avenida Centenario 100')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_colegio', [
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'direccion_col' => 'Avenida Centenario 100',
        'estado_col' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update a colegio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_editar']);

    $tipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PUB',
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ]);

    $otroTipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PRI',
        'nombre_tco' => 'PRIVADO',
        'estado_tco' => 'HABILITADO',
    ]);

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

    $otroUbigeo = Ubigeo::query()->create([
        'id_ubi' => '250201',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '02',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'PADRE ABAD',
        'nombre_distrito_ubi' => 'AGUAYTIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    $colegio = Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'direccion_col' => 'AVENIDA CENTENARIO 100',
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->call('cargarColegio', $colegio->id_col)
        ->set('colegioForm.codigo_modular', '0915390')
        ->set('colegioForm.nombre', 'Agropecuario Padre Abad Privado')
        ->set('colegioForm.id_tipo_colegio', (string) $otroTipoColegio->id_tco)
        ->set('colegioForm.id_ubigeo', $otroUbigeo->id_ubi)
        ->set('colegioForm.direccion', 'Jiron Central 123')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_colegio', [
        'id_col' => '0915389',
        'codigo_modular_col' => '0915390',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD PRIVADO',
        'id_tipo_colegio' => $otroTipoColegio->id_tco,
        'id_ubigeo' => $otroUbigeo->id_ubi,
        'direccion_col' => 'Jiron Central 123',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a colegio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_estado']);

    $colegio = Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->call('cambiarEstado', $colegio->id_col)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_colegio', [
        'id_col' => $colegio->id_col,
        'estado_col' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a colegio without inscripciones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_eliminar']);

    $colegio = Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->call('eliminar', $colegio->id_col)
        ->assertHasNoErrors();

    $colegioEliminado = Colegio::query()->withTrashed()->find($colegio->id_col);

    expect($colegioEliminado)->not->toBeNull();
    expect($colegioEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('authenticated users cannot register duplicated colegio by codigo modular or name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_crear']);

    Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->set('colegioForm.id', '0915390')
        ->set('colegioForm.codigo_modular', '0915389')
        ->set('colegioForm.nombre', 'Agropecuario Padre Abad')
        ->call('guardar')
        ->assertHasErrors([
            'colegioForm.codigo_modular',
            'colegioForm.nombre',
        ]);
});

test('authenticated users cannot delete a colegio with inscripciones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosColegio($usuario, ['colegio_indice', 'colegio_eliminar']);

    $tipoDocumento = TipoDocumento::query()->create([
        'codigo_tdo' => 'DNI',
        'nombre_tdo' => 'DNI',
        'longitud_minima_tdo' => 8,
        'longitud_maxima_tdo' => 8,
        'estado_tdo' => 'HABILITADO',
    ]);

    $estadoCivil = EstadoCivil::query()->create([
        'codigo_eci' => 'SOL',
        'nombre_eci' => 'SOLTERO(A)',
        'estado_eci' => 'HABILITADO',
    ]);

    $pais = Pais::query()->create([
        'codigo_pai' => 'PE',
        'nombre_pai' => 'PERU',
        'estado_pai' => 'HABILITADO',
    ]);

    $nacionalidad = Nacionalidad::query()->create([
        'codigo_nac' => 'PER',
        'nombre_nac' => 'PERUANA',
        'estado_nac' => 'HABILITADO',
    ]);

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

    $colegio = Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_col' => 'HABILITADO',
    ]);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_1',
        'nombre_sem' => 'SEMESTRE 2026-1',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_1',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => 'MAD1',
        'nombre_mad' => 'MODALIDAD 1',
        'estado_mad' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => 'SEDE1',
        'nombre_sed' => 'SEDE 1',
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $lugarInscripcion = LugarInscripcion::query()->create([
        'codigo_lin' => 'LIN1',
        'nombre_lin' => 'LUGAR 1',
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);

    $carrera = Carrera::query()->create([
        'codigo_car' => 'CAR1',
        'nombre_car' => 'CARRERA 1',
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SCA1',
        'estado_sca' => 'HABILITADO',
    ]);

    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugarInscripcion->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $ofertaBase = OfertaBase::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugarInscripcion->id_lin,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => 'TUR1',
        'nombre_tur' => 'MANANA',
        'estado_tur' => 'HABILITADO',
    ]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '74503321',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'FALCON',
        'apellido_materno_per' => 'ROJAS',
        'nombres_per' => 'LUCY BETTY',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'estado_per' => 'HABILITADO',
    ]);

    $procesoOferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $ofertaBase->id_ofb,
        'id_turno' => $turno->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    Inscripcion::query()->create([
        'codigo_ins' => 'INS-001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_colegio' => $colegio->id_col,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => 'PENDIENTE',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.colegios')
        ->call('eliminar', $colegio->id_col)
        ->assertDispatched('toast', message: 'El colegio tiene inscripciones relacionadas y no puede eliminarse.', type: 'error');

    expect(Colegio::query()->find($colegio->id_col))->not->toBeNull();
});
