<?php

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoValidacionPago;
use App\Models\Accion;
use App\Models\Colegio;
use App\Models\ConvenioProceso;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\TipoColegio;
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ColegioSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\EstadoCivilSeeder;
use Database\Seeders\IdentidadEtnicaSeeder;
use Database\Seeders\LenguaExtranjeraSeeder;
use Database\Seeders\LenguaNativaSeeder;
use Database\Seeders\LugarInscripcionSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\ModalidadLugarInscripcionSeeder;
use Database\Seeders\NacionalidadSeeder;
use Database\Seeders\OfertaBaseSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\SedeCarreraSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoColegioSeeder;
use Database\Seeders\TipoDiscapacidadSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\TipoExpedienteSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

function sembrarConfiguracionAcademicaParaInscripcion(): void
{
    foreach ([
        TurnoSeeder::class,
        TipoExpedienteSeeder::class,
        ConceptoPagoSeeder::class,
        TipoDocumentoSeeder::class,
        TipoDiscapacidadSeeder::class,
        TipoColegioSeeder::class,
        EstadoCivilSeeder::class,
        PaisSeeder::class,
        NacionalidadSeeder::class,
        UbigeoSeeder::class,
        IdentidadEtnicaSeeder::class,
        LenguaNativaSeeder::class,
        LenguaExtranjeraSeeder::class,
        ColegioSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        LugarInscripcionSeeder::class,
        ModalidadAdmisionSeeder::class,
        ModalidadLugarInscripcionSeeder::class,
        SedeCarreraSeeder::class,
        OfertaBaseSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--no-interaction' => true]);
    }
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

test('users can register general information and receive a warning when there is no active process yet', function () {
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
        ->assertDispatched('toast', message: 'Informacion general guardada. No hay un proceso de inscripcion activo en este momento.', type: 'warning');
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

test('users can register postulacion and create an inscripcion draft', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()
        ->where('longitud_minima_tdo', 8)
        ->where('longitud_maxima_tdo', 8)
        ->firstOrFail();

    $estadoCivil = EstadoCivil::query()->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $nacionalidad = Nacionalidad::query()->firstOrFail();
    $ubigeo = Ubigeo::query()->firstOrFail();
    $identidadEtnica = IdentidadEtnica::query()->firstOrFail();
    $lenguaNativa = LenguaNativa::query()->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $identidadEtnica->id_iet,
        'id_lengua_nativa' => $lenguaNativa->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()
        ->where('codigo_pro', 'ordinario_2026_1')
        ->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with([
            'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
            'ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
            'turno',
        ])
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->set('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->set('postulacionForm.id_convenio_proceso', (string) $convenioProceso->id_cpr)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 4)
        ->assertDispatched('toast', message: 'Postulacion guardada correctamente.', type: 'success');

    $inscripcion = Inscripcion::query()->first();

    expect($inscripcion)->not->toBeNull()
        ->and($inscripcion?->id_persona)->toBe($persona->id_per)
        ->and($inscripcion?->id_proceso_oferta)->toBe($procesoOferta->id_pof)
        ->and($inscripcion?->id_convenio_proceso)->toBe($convenioProceso->id_cpr)
        ->and($inscripcion?->estado_ins?->value)->toBe('PENDIENTE')
        ->and($inscripcion?->veces_postula_unu_ins)->toBe(0)
        ->and($inscripcion?->veces_postula_otras_ins)->toBe(0)
        ->and($inscripcion?->codigo_ins)->not->toStartWith('TMP-')
        ->and($inscripcion?->codigo_ins)->toStartWith('261ORD');

    Carbon\Carbon::setTestNow();
});

test('users can continue an existing inscripcion with the wizard preloaded', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()
        ->where('longitud_minima_tdo', 8)
        ->where('longitud_maxima_tdo', 8)
        ->firstOrFail();

    $estadoCivil = EstadoCivil::query()->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $nacionalidad = Nacionalidad::query()->firstOrFail();
    $ubigeo = Ubigeo::query()->firstOrFail();
    $identidadEtnica = IdentidadEtnica::query()->firstOrFail();
    $lenguaNativa = LenguaNativa::query()->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $identidadEtnica->id_iet,
        'id_lengua_nativa' => $lenguaNativa->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => 'REGISTRO EXISTENTE',
        'estado_per' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()
        ->where('codigo_pro', 'ordinario_2026_1')
        ->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with([
            'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
            'ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
            'turno',
        ])
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => '26IPRO0001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => '2026-04-15 10:00:00',
        'estado_ins' => EstadoInscripcion::Pendiente->value,
        'anio_graduacion_ins' => 2025,
        'id_pais_colegio' => $pais->id_pai,
        'id_colegio' => $colegio->id_col,
        'nombre_colegio_extranjero_ins' => null,
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'veces_postula_unu_ins' => 1,
        'veces_postula_otras_ins' => 2,
        'observacion_ins' => 'OBSERVACION GENERAL',
    ]);

    $this->actingAs($usuario)
        ->get(route('inscripciones.continuar', $inscripcion->id_ins))
        ->assertOk()
        ->assertSee('NUEVA INSCRIPCION');

    $ubigeoLabel = "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    $colegioLabel = "{$colegio->id_col} | {$colegio->nombre_col}";

    Livewire::test('pages::inscripciones.inscripcion-crear', ['inscripcion' => $inscripcion->id_ins])
        ->assertSet('inscripcionId', $inscripcion->id_ins)
        ->assertSet('personaId', $persona->id_per)
        ->assertSet('pasoActual', 4)
        ->assertSet('personaForm.id_tipo_documento', (string) $tipoDocumento->id_tdo)
        ->assertSet('personaForm.numero_documento', '46027897')
        ->assertSet('personaForm.nombres', 'ROXANA KARINA')
        ->assertSet('ubigeoNacimientoLabel', $ubigeoLabel)
        ->assertSet('ubigeoDomicilioLabel', $ubigeoLabel)
        ->assertSet('generalForm.anio_graduacion', '2025')
        ->assertSet('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->assertSet('generalForm.id_colegio', $colegio->id_col)
        ->assertSet('generalForm.veces_postula_unu', 1)
        ->assertSet('generalForm.veces_postula_otras', 2)
        ->assertSet('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->assertSet('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->assertSet('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->assertSet('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->assertSet('postulacionForm.id_convenio_proceso', (string) $convenioProceso->id_cpr)
        ->assertSet('pagoForm.numero_documento', '46027897')
        ->assertSet('colegioLabel', $colegioLabel)
        ->assertDispatched('ui-select-search-filled', name: 'generalForm.id_colegio', value: $colegio->id_col, label: $colegioLabel);
});

test('users can validate a bank payment from the payment step and continue to expedientes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()
        ->where('longitud_minima_tdo', 8)
        ->where('longitud_maxima_tdo', 8)
        ->firstOrFail();

    $estadoCivil = EstadoCivil::query()->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $nacionalidad = Nacionalidad::query()->firstOrFail();
    $ubigeo = Ubigeo::query()->firstOrFail();
    $identidadEtnica = IdentidadEtnica::query()->firstOrFail();
    $lenguaNativa = LenguaNativa::query()->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $identidadEtnica->id_iet,
        'id_lengua_nativa' => $lenguaNativa->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()
        ->where('codigo_pro', 'ordinario_2026_1')
        ->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with([
            'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
            'ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
            'turno',
        ])
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $reglaPago = ReglaPago::query()
        ->where('id_proceso', $proceso->id_pro)
        ->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', 'INS_CUO'))
        ->firstOrFail();

    $importacion = ImportacionBanco::query()->create([
        'nombre_archivo_iba' => 'REC10768.TXT',
        'hash_archivo_iba' => 'hash-paso-4',
        'moneda_iba' => 'PEN',
        'fecha_importacion_iba' => '2026-04-15 08:00:00',
        'fecha_lote_iba' => '2026-04-14',
        'total_registros_iba' => 1,
        'total_registros_reportado_iba' => 1,
        'total_monto_iba' => 300.00,
        'total_monto_reportado_iba' => 300.00,
        'estado_iba' => EstadoImportacionBanco::Procesado,
    ]);

    $detalle = ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $importacion->id_iba,
        'numero_documento_ibd' => $persona->numero_documento_per,
        'nombre_depositante_ibd' => 'ROXANA KARINA DELGADO HUAMANI',
        'numero_operacion_raw_ibd' => '817041676',
        'numero_operacion_visible_ibd' => '41676',
        'numero_operacion_ibd' => '817041676',
        'codigo_canal_ibd' => 'Y%G',
        'codigo_oficina_ibd' => '01068',
        'fecha_pago_ibd' => '2026-04-14',
        'monto_ibd' => 300.00,
        'texto_linea_ibd' => str_repeat('0', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->set('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->call('guardarPasoPostulacion')
        ->assertSet('pasoActual', 4)
        ->set('pagoForm.id_regla_pago', (string) $reglaPago->id_rpa)
        ->set('pagoForm.numero_operacion', '41676')
        ->set('pagoForm.fecha_pago', '2026-04-14')
        ->call('buscarPagosPasoPago')
        ->assertSet('resultadoPagoDetalleIds', [$detalle->id_ibd])
        ->call('seleccionarDetallePago', $detalle->id_ibd)
        ->call('confirmarPasoPago')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 5)
        ->assertDispatched('toast', message: 'Pago validado correctamente. Puede continuar con los expedientes.', type: 'success');

    $inscripcion = Inscripcion::query()->firstOrFail();
    $obligacion = InscripcionObligacionPago::query()->where('id_inscripcion', $inscripcion->id_ins)->firstOrFail();
    $pago = InscripcionPago::query()->where('id_inscripcion_obligacion_pago', $obligacion->id_iop)->firstOrFail();

    expect($obligacion->id_regla_pago)->toBe($reglaPago->id_rpa)
        ->and((float) $obligacion->monto_total_iop)->toBe(550.0)
        ->and((float) $obligacion->monto_pagado_iop)->toBe(300.0)
        ->and((float) $obligacion->saldo_iop)->toBe(250.0)
        ->and($obligacion->estado_iop)->toBe(EstadoObligacionPago::Parcial)
        ->and($pago->estado_validacion_ipa)->toBe(EstadoValidacionPago::Verificado);

    expect($detalle->fresh()->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Asociado);

    Carbon\Carbon::setTestNow();
});

test('users can save expediente review and activate the inscripcion when all required documents are approved', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();
    Storage::fake('public');

    $tipoDocumento = TipoDocumento::query()
        ->where('longitud_minima_tdo', 8)
        ->where('longitud_maxima_tdo', 8)
        ->firstOrFail();

    $estadoCivil = EstadoCivil::query()->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $nacionalidad = Nacionalidad::query()->firstOrFail();
    $ubigeo = Ubigeo::query()->firstOrFail();
    $identidadEtnica = IdentidadEtnica::query()->firstOrFail();
    $lenguaNativa = LenguaNativa::query()->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '46027897',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'apellido_casada_per' => null,
        'nombres_per' => 'ROXANA KARINA',
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'telefono_per' => '061999999',
        'correo_electronico_per' => 'roxana@test.com',
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $identidadEtnica->id_iet,
        'id_lengua_nativa' => $lenguaNativa->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()
        ->where('codigo_pro', 'ordinario_2026_1')
        ->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with([
            'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
            'ofertaBase.modalidadLugarInscripcion.lugarInscripcion',
            'turno',
        ])
        ->where('id_proceso', $proceso->id_pro)
        ->firstOrFail();

    $reglaPago = ReglaPago::query()
        ->where('id_proceso', $proceso->id_pro)
        ->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', 'INS_CUO'))
        ->firstOrFail();

    $importacion = ImportacionBanco::query()->create([
        'nombre_archivo_iba' => 'REC10768.TXT',
        'hash_archivo_iba' => 'hash-paso-5',
        'moneda_iba' => 'PEN',
        'fecha_importacion_iba' => '2026-04-15 08:00:00',
        'fecha_lote_iba' => '2026-04-14',
        'total_registros_iba' => 1,
        'total_registros_reportado_iba' => 1,
        'total_monto_iba' => 300.00,
        'total_monto_reportado_iba' => 300.00,
        'estado_iba' => EstadoImportacionBanco::Procesado,
    ]);

    $detalle = ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $importacion->id_iba,
        'numero_documento_ibd' => $persona->numero_documento_per,
        'nombre_depositante_ibd' => 'ROXANA KARINA DELGADO HUAMANI',
        'numero_operacion_raw_ibd' => '817041676',
        'numero_operacion_visible_ibd' => '41676',
        'numero_operacion_ibd' => '817041676',
        'codigo_canal_ibd' => 'Y%G',
        'codigo_oficina_ibd' => '01068',
        'fecha_pago_ibd' => '2026-04-14',
        'monto_ibd' => 300.00,
        'texto_linea_ibd' => str_repeat('0', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $component = Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->set('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->call('guardarPasoPostulacion')
        ->set('pagoForm.id_regla_pago', (string) $reglaPago->id_rpa)
        ->set('pagoForm.numero_operacion', '41676')
        ->set('pagoForm.fecha_pago', '2026-04-14')
        ->call('buscarPagosPasoPago')
        ->call('seleccionarDetallePago', $detalle->id_ibd)
        ->call('confirmarPasoPago')
        ->assertSet('pasoActual', 5);

    foreach (range(0, 2) as $indice) {
        $component
            ->set("expedientesForm.{$indice}.estado_revision", EstadoRevisionExpediente::Aprobado->value)
            ->set("expedientesForm.{$indice}.nombre_archivo", 'REV-'.$indice)
            ->set("expedientesForm.{$indice}.observacion", 'DOCUMENTO APROBADO')
            ->set("archivosExpediente.{$indice}", UploadedFile::fake()->create("expediente-{$indice}.pdf", 120, 'application/pdf'));
    }

    $component
        ->call('guardarPasoExpedientes')
        ->assertRedirect(route('inscripciones.index'));

    $inscripcion = Inscripcion::query()->firstOrFail();

    expect($inscripcion->estado_ins)->toBe(EstadoInscripcion::Activo);
    expect(InscripcionExpediente::query()->where('id_inscripcion', $inscripcion->id_ins)->count())->toBe(3);
    expect(
        InscripcionExpediente::query()
            ->where('id_inscripcion', $inscripcion->id_ins)
            ->where('estado_revision_iex', EstadoRevisionExpediente::Aprobado->value)
            ->count()
    )->toBe(3);

    Carbon\Carbon::setTestNow();
});

test('inscripcion is rejected when convenio cupo is already full at validation step', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase.modalidadLugarInscripcion'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $convenioProceso->update(['cupo_maximo_cpr' => 1]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '12345678',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'TORRES',
        'apellido_materno_per' => 'GARCIA',
        'nombres_per' => 'JUAN',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $personaExistente = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '87654321',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'LOPEZ',
        'apellido_materno_per' => 'VERA',
        'nombres_per' => 'MARIA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    Inscripcion::query()->create([
        'codigo_ins' => 'TEST-001',
        'id_persona' => $personaExistente->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => 'ACTIVO',
    ]);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->set('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->set('postulacionForm.id_convenio_proceso', (string) $convenioProceso->id_cpr)
        ->call('guardarPasoPostulacion')
        ->assertHasErrors('postulacionForm.id_convenio_proceso');

    expect(Inscripcion::query()->count())->toBe(1);

    Carbon\Carbon::setTestNow();
});

test('inscripcion with convenio without cupo_maximo is always allowed', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase.modalidadLugarInscripcion'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $convenioProceso->update(['cupo_maximo_cpr' => 100]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '11223344',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'ROJAS',
        'apellido_materno_per' => 'LIMA',
        'nombres_per' => 'CARLOS',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_modalidad_admision', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->set('postulacionForm.id_lugar_inscripcion', (string) $procesoOferta->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('postulacionForm.id_proceso_oferta', (string) $procesoOferta->id_pof)
        ->set('postulacionForm.id_convenio_proceso', (string) $convenioProceso->id_cpr)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 4);

    Carbon\Carbon::setTestNow();
});

test('buscarPagosPasoPago is blocked when proceso is no longer active', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase.modalidadLugarInscripcion'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '11223355',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'PEREZ',
        'apellido_materno_per' => 'LARA',
        'nombres_per' => 'ANA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-PAGO-001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    Carbon\Carbon::setTestNow('2099-01-01 10:00:00');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('inscripcionId', $inscripcion->id_ins)
        ->set('pasoActual', 4)
        ->call('buscarPagosPasoPago')
        ->assertDispatched('toast', type: 'warning');

    Carbon\Carbon::setTestNow();
});

test('confirmarPasoPago is blocked when proceso is no longer active', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase.modalidadLugarInscripcion'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '11223366',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'SOTO',
        'apellido_materno_per' => 'CRUZ',
        'nombres_per' => 'LUIS',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-PAGO-002',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    Carbon\Carbon::setTestNow('2099-01-01 10:00:00');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('inscripcionId', $inscripcion->id_ins)
        ->set('pasoActual', 4)
        ->call('confirmarPasoPago')
        ->assertDispatched('toast', type: 'warning');

    Carbon\Carbon::setTestNow();
});

test('guardarPasoExpedientes is blocked when proceso is no longer active', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase.modalidadLugarInscripcion'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $convenioProceso = ConvenioProceso::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '11223377',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'VARGAS',
        'apellido_materno_per' => 'RIOS',
        'nombres_per' => 'SARA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-EXP-001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    Carbon\Carbon::setTestNow('2099-01-01 10:00:00');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('inscripcionId', $inscripcion->id_ins)
        ->set('pasoActual', 5)
        ->call('guardarPasoExpedientes')
        ->assertDispatched('toast', type: 'warning');

    Carbon\Carbon::setTestNow();
});
