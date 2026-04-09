<?php

use App\Models\Accion;
use App\Models\Colegio;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\TipoColegio;
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosInscripcionCrear(Usuario $usuario, array $claves): void
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

function crearCatalogosPasoPersonal(): array
{
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

    $identidadEtnica = IdentidadEtnica::query()->create([
        'codigo_iet' => 'MES',
        'nombre_iet' => 'MESTIZO',
        'estado_iet' => 'HABILITADO',
    ]);

    $lenguaNativa = LenguaNativa::query()->create([
        'codigo_lna' => 'ESP',
        'nombre_lna' => 'ESPANOL',
        'estado_lna' => 'HABILITADO',
    ]);

    $lenguaExtranjera = LenguaExtranjera::query()->create([
        'codigo_lex' => 'ING',
        'nombre_lex' => 'INGLES',
        'estado_lex' => 'HABILITADO',
    ]);

    $tipoDiscapacidad = TipoDiscapacidad::query()->create([
        'codigo_tdi' => 'VIS',
        'nombre_tdi' => 'VISUAL',
        'estado_tdi' => 'HABILITADO',
    ]);

    $tipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PUB',
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ]);

    $colegio = Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_col' => 'HABILITADO',
    ]);

    return compact(
        'tipoDocumento',
        'estadoCivil',
        'pais',
        'nacionalidad',
        'ubigeo',
        'identidadEtnica',
        'lenguaNativa',
        'lenguaExtranjera',
        'tipoDiscapacidad',
        'tipoColegio',
        'colegio',
    );
}

test('authorized users can access nueva inscripcion page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $this->actingAs($usuario)
        ->get(route('inscripciones.crear'))
        ->assertOk()
        ->assertSee('NUEVA INSCRIPCION');
});

test('users can register personal information for a new inscripcion draft', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '74503321')
        ->set('personaForm.solo_un_apellido', false)
        ->set('personaForm.tiene_apellido_casada', false)
        ->set('personaForm.apellido_paterno', 'Falcon')
        ->set('personaForm.apellido_materno', 'Rojas')
        ->set('personaForm.apellido_casada', '')
        ->set('personaForm.nombres', 'Lucy Betty')
        ->set('personaForm.fecha_nacimiento', '2002-04-15')
        ->set('personaForm.sexo', 'FEMENINO')
        ->set('personaForm.id_estado_civil', (string) $catalogos['estadoCivil']->id_eci)
        ->set('personaForm.id_pais_nacimiento', (string) $catalogos['pais']->id_pai)
        ->set('personaForm.id_nacionalidad', (string) $catalogos['nacionalidad']->id_nac)
        ->set('personaForm.id_ubigeo_nacimiento', $catalogos['ubigeo']->id_ubi)
        ->set('personaForm.id_ubigeo_domicilio', $catalogos['ubigeo']->id_ubi)
        ->set('personaForm.direccion', 'Jr. Principal 123')
        ->set('personaForm.celular', '987654321')
        ->set('personaForm.telefono', '061123456')
        ->set('personaForm.correo_electronico', 'lucy@test.com')
        ->set('personaForm.discapacidad', true)
        ->set('personaForm.id_tipo_discapacidad', (string) $catalogos['tipoDiscapacidad']->id_tdi)
        ->set('personaForm.tipo_lengua_materna', 'NATIVA')
        ->set('personaForm.id_identidad_etnica', (string) $catalogos['identidadEtnica']->id_iet)
        ->set('personaForm.id_lengua_nativa', (string) $catalogos['lenguaNativa']->id_lna)
        ->set('personaForm.id_lengua_extranjera', '')
        ->set('personaForm.observacion', 'Observacion base')
        ->call('guardarPasoPersonal')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2);

    $persona = Persona::query()->first();

    expect($persona)->not->toBeNull();
    expect($persona?->numero_documento_per)->toBe('74503321');
    expect($persona?->apellido_paterno_per)->toBe('FALCON');
    expect($persona?->apellido_materno_per)->toBe('ROJAS');
    expect($persona?->nombres_per)->toBe('LUCY BETTY');
    expect($persona?->correo_electronico_per)->toBe('lucy@test.com');
    expect($persona?->discapacidad_per)->toBeTrue();
});

test('existing person data is loaded from the local system before consulting reniec', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => 'REGISTRO EXISTENTE',
        'estado_per' => 'HABILITADO',
    ]);

    Http::fake();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('personaId', $persona->id_per)
        ->assertSet('personaForm.nombres', 'ROXANA KARINA')
        ->assertSet('personaForm.apellido_paterno', 'DELGADO')
        ->assertSet('personaForm.apellido_materno', 'HUAMANI')
        ->assertSet('personaForm.correo_electronico', 'roxana@test.com')
        ->assertSet('personaForm.tipo_lengua_materna', 'NATIVA')
        ->assertSet('ubigeoNacimientoLabel', '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA')
        ->assertSet('ubigeoDomicilioLabel', '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA')
        ->assertSet('reniecTipoMensaje', 'success')
        ->assertDispatched('ui-select-search-filled', name: 'personaForm.id_ubigeo_nacimiento', value: '250101', label: '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA')
        ->assertDispatched('ui-select-search-filled', name: 'personaForm.id_ubigeo_domicilio', value: '250101', label: '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA');

    Http::assertNothingSent();
});

test('changing document after autoload resets the previously loaded person context', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => 'REGISTRO EXISTENTE',
        'estado_per' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('personaId', $persona->id_per)
        ->set('personaForm.numero_documento', '46027898')
        ->assertSet('personaId', null)
        ->assertSet('origenDatosPersona', null)
        ->assertSet('personaForm.numero_documento', '46027898')
        ->assertSet('personaForm.nombres', '')
        ->assertSet('personaForm.correo_electronico', '')
        ->assertSet('personaForm.id_ubigeo_nacimiento', '')
        ->assertSet('personaForm.tipo_lengua_materna', '')
        ->assertSet('ubigeoNacimientoLabel', '')
        ->assertSet('ubigeoDomicilioLabel', '')
        ->assertSet('reniecMensaje', null);
});

test('dni can be consulted with reniec and autofill personal names when there is no local match', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'first_name' => 'ROXANA KARINA',
            'first_last_name' => 'DELGADO',
            'second_last_name' => 'HUAMANI',
            'full_name' => 'DELGADO HUAMANI ROXANA KARINA',
            'document_number' => '46027897',
        ]),
    ]);

    Config::set('services.reniec.url', 'https://api.decolecta.com');
    Config::set('services.reniec.token', 'token-prueba');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('personaForm.numero_documento', '46027897')
        ->assertSet('personaForm.nombres', 'ROXANA KARINA')
        ->assertSet('personaForm.apellido_paterno', 'DELGADO')
        ->assertSet('personaForm.apellido_materno', 'HUAMANI')
        ->assertSet('reniecTipoMensaje', 'success');
});

test('reniec lookup clears stale complementary data from a previously loaded local person', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => true,
        'id_tipo_discapacidad' => $catalogos['tipoDiscapacidad']->id_tdi,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => 'REGISTRO EXISTENTE',
        'estado_per' => 'HABILITADO',
    ]);

    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'first_name' => 'MARIA LUISA',
            'first_last_name' => 'PEREZ',
            'second_last_name' => 'RAMIREZ',
            'full_name' => 'PEREZ RAMIREZ MARIA LUISA',
            'document_number' => '46027899',
        ]),
    ]);

    Config::set('services.reniec.url', 'https://api.decolecta.com');
    Config::set('services.reniec.token', 'token-prueba');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->set('personaForm.numero_documento', '46027899')
        ->call('buscarDatosPersona')
        ->assertSet('personaId', null)
        ->assertSet('origenDatosPersona', 'reniec')
        ->assertSet('personaForm.numero_documento', '46027899')
        ->assertSet('personaForm.nombres', 'MARIA LUISA')
        ->assertSet('personaForm.apellido_paterno', 'PEREZ')
        ->assertSet('personaForm.apellido_materno', 'RAMIREZ')
        ->assertSet('personaForm.correo_electronico', '')
        ->assertSet('personaForm.id_ubigeo_nacimiento', '')
        ->assertSet('personaForm.id_ubigeo_domicilio', '')
        ->assertSet('personaForm.discapacidad', false)
        ->assertSet('personaForm.id_tipo_discapacidad', '')
        ->assertSet('personaForm.tipo_lengua_materna', '')
        ->assertSet('ubigeoNacimientoLabel', '')
        ->assertSet('ubigeoDomicilioLabel', '')
        ->assertSet('reniecTipoMensaje', 'success');
});

test('non dni documents keep manual flow and warn when there is no local match', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $tipoDocumento = TipoDocumento::query()->create([
        'codigo_tdo' => 'CE',
        'nombre_tdo' => 'CARNET DE EXTRANJERIA',
        'longitud_minima_tdo' => 9,
        'longitud_maxima_tdo' => 12,
        'estado_tdo' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.id_tipo_documento', (string) $tipoDocumento->id_tdo)
        ->set('personaForm.numero_documento', 'ABC123456')
        ->call('buscarDatosPersona')
        ->assertSet('reniecTipoMensaje', 'warning')
        ->assertSet('reniecMensaje', 'No se encontraron datos registrados. Complete el formulario manualmente.');
});

test('users can register general information and continue to postulacion step', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 2)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $catalogos['pais']->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $catalogos['tipoColegio']->id_tco)
        ->set('generalForm.id_colegio', $catalogos['colegio']->id_col)
        ->assertSet('colegioLabel', '0915389 | AGROPECUARIO PADRE ABAD')
        ->set('generalForm.veces_postula_unu', '1')
        ->set('generalForm.veces_postula_otras', '2')
        ->set('generalForm.observacion', 'OBSERVACION GENERAL')
        ->call('guardarPasoGeneral')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 3)
        ->assertSet('generalForm.nombre_colegio_extranjero', '')
        ->assertDispatched('toast', message: 'Informacion general guardada correctamente.', type: 'success');
});

test('general information validates future graduation year and required postulation counts', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 2)
        ->set('generalForm.anio_graduacion', (string) (((int) now()->format('Y')) + 1))
        ->set('generalForm.id_pais_colegio', (string) $catalogos['pais']->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $catalogos['tipoColegio']->id_tco)
        ->set('generalForm.id_colegio', $catalogos['colegio']->id_col)
        ->set('generalForm.veces_postula_unu', '')
        ->set('generalForm.veces_postula_otras', '')
        ->call('guardarPasoGeneral')
        ->assertHasErrors([
            'generalForm.anio_graduacion',
            'generalForm.veces_postula_unu',
            'generalForm.veces_postula_otras',
        ])
        ->assertSet('pasoActual', 2);
});

test('cannot jump to postulacion step when general information is invalid', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $catalogos['tipoDocumento']->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $catalogos['estadoCivil']->id_eci,
        'id_pais_nacimiento' => $catalogos['pais']->id_pai,
        'id_nacionalidad' => $catalogos['nacionalidad']->id_nac,
        'id_ubigeo_nacimiento' => $catalogos['ubigeo']->id_ubi,
        'id_ubigeo_domicilio' => $catalogos['ubigeo']->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $catalogos['identidadEtnica']->id_iet,
        'id_lengua_nativa' => $catalogos['lenguaNativa']->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 2)
        ->set('generalForm.anio_graduacion', '')
        ->call('irPaso', 3)
        ->assertSet('pasoActual', 2)
        ->assertHasErrors(['generalForm.anio_graduacion'])
        ->assertDispatched('toast', message: 'Revise la informacion general antes de continuar.', type: 'warning');
});
