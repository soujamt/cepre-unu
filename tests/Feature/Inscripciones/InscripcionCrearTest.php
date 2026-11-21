<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Colegio;
use App\Models\ConceptoPago;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\ModalidadAdmision;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\TipoColegio;
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use App\Models\Turno;
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
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\NacionalidadSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoColegioSeeder;
use Database\Seeders\TipoDiscapacidadSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Support\AsistentePrueba;

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
        ModalidadAdmisionSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--no-interaction' => true]);
    }
}

/**
 * Primera carrera habilitada del proceso semilla 2026-1 con el turno MAÑANA y
 * la modalidad ORDINARIO, para armar postulaciones de prueba. Con $conAula
 * le asigna un aula al turno para que se pueda elegir.
 */
function postulacionDePrueba(bool $conAula = false): object
{
    $procesoCarrera = ProcesoCarrera::query()
        ->whereHas('proceso', fn ($query) => $query->where('codigo_pro', '2026_1'))
        ->orderBy('id_pca')
        ->firstOrFail();
    $turno = Turno::query()->where('codigo_tur', 'MAN')->firstOrFail();

    if ($conAula) {
        crearAulaParaProcesoInscripcion($procesoCarrera->proceso, $turno, 'AULA PRUEBA', 'UAP', 'PISO 1', 40);
    }

    return (object) [
        'id_proceso' => $procesoCarrera->id_proceso,
        'id_sede' => $procesoCarrera->id_sede,
        'id_carrera' => $procesoCarrera->id_carrera,
        'id_modalidad_admision' => ModalidadAdmision::query()->where('codigo_mad', 'ORD')->value('id_mad'),
        'id_turno' => $turno->id_tur,
        'turno' => $turno,
    ];
}

function crearPersonaParaPostulacion(string $numeroDocumento): Persona
{
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

    return Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => $numeroDocumento,
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
        'correo_electronico_per' => "{$numeroDocumento}@test.com",
        'discapacidad_per' => false,
        'id_tipo_discapacidad' => null,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => $identidadEtnica->id_iet,
        'id_lengua_nativa' => $lenguaNativa->id_lna,
        'id_lengua_extranjera' => null,
        'observacion_per' => null,
        'estado_per' => 'HABILITADO',
    ]);
}

/**
 * @return array{aula: Aula, procesoAula: ProcesoAula}
 */
function crearAulaParaProcesoInscripcion(Proceso $proceso, Turno $turno, string $nombre, string $lugar, string $piso, int $capacidad): array
{
    $aula = Aula::query()->create([
        'nombre_aul' => $nombre,
        'lugar_aul' => $lugar,
        'piso_aul' => $piso,
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    $procesoAula = ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => $capacidad,
        'estado_pau' => 'HABILITADO',
    ]);

    return compact('aula', 'procesoAula');
}

test('authorized users can access nueva inscripcion page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $this->actingAs($usuario)
        ->followingRedirects()
        ->get(route('inscripciones.crear'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('inscripciones/asistente')->where('asistente.paso', 1)->has('token'));
});

test('users can register personal information for a new inscripcion draft', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $catalogos = [
        'tipoDocumento' => TipoDocumento::query()->where('codigo_tdo', '1')->firstOrFail(),
        'estadoCivil' => EstadoCivil::query()->orderBy('id_eci')->firstOrFail(),
        'pais' => Pais::query()->orderBy('id_pai')->firstOrFail(),
        'nacionalidad' => Nacionalidad::query()->orderBy('id_nac')->firstOrFail(),
        'ubigeo' => Ubigeo::query()->where('id_ubi', '250101')->firstOrFail(),
        'identidadEtnica' => IdentidadEtnica::query()->orderBy('id_iet')->firstOrFail(),
        'lenguaNativa' => LenguaNativa::query()->orderBy('id_lna')->firstOrFail(),
        'tipoDiscapacidad' => TipoDiscapacidad::query()->orderBy('id_tdi')->firstOrFail(),
    ];

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
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
        ->set('personaForm.nombre_apoderado', 'maria rojas')
        ->set('personaForm.celular_apoderado', '944477117')
        ->set('personaForm.discapacidad', true)
        ->set('personaForm.id_tipo_discapacidad', (string) $catalogos['tipoDiscapacidad']->id_tdi)
        ->set('personaForm.tipo_lengua_materna', 'NATIVA')
        ->set('personaForm.id_identidad_etnica', (string) $catalogos['identidadEtnica']->id_iet)
        ->set('personaForm.id_lengua_nativa', (string) $catalogos['lenguaNativa']->id_lna)
        ->set('personaForm.id_lengua_extranjera', '')
        ->set('personaForm.observacion', 'Observacion base')
        ->call('guardarPasoPersonal')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 3);

    $persona = Persona::query()->first();

    expect($persona)->not->toBeNull();
    expect($persona?->numero_documento_per)->toBe('74503321');
    expect($persona?->apellido_paterno_per)->toBe('FALCON');
    expect($persona?->apellido_materno_per)->toBe('ROJAS');
    expect($persona?->nombres_per)->toBe('LUCY BETTY');
    expect($persona?->correo_electronico_per)->toBe('lucy@test.com');
    expect($persona?->discapacidad_per)->toBeTrue();
    expect($persona?->nombre_apoderado_per)->toBe('MARIA ROJAS');
    expect($persona?->celular_apoderado_per)->toBe('944477117');

    Carbon\Carbon::setTestNow();
});

test('personal information requires valid cellphones and the guardian contact', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('pasoActual', 2)
        ->set('personaForm.celular', '812345678')
        ->set('personaForm.celular_apoderado', '9123')
        ->call('guardarPasoPersonal')
        ->assertHasErrors([
            'personaForm.celular' => 'regex',
            'personaForm.celular_apoderado' => 'regex',
            'personaForm.nombre_apoderado' => 'required',
            'personaForm.correo_electronico' => 'required',
        ]);

    Carbon\Carbon::setTestNow();
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

    AsistentePrueba::personal(test())
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

    AsistentePrueba::personal(test())
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

    AsistentePrueba::personal(test())
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('personaForm.numero_documento', '46027897')
        ->assertSet('personaForm.nombres', 'ROXANA KARINA')
        ->assertSet('personaForm.apellido_paterno', 'DELGADO')
        ->assertSet('personaForm.apellido_materno', 'HUAMANI')
        ->assertSet('reniecTipoMensaje', 'success');
});

test('dni lookup shows a friendly warning when reniec is not configured', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    Config::set('services.reniec.url', null);

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('personaForm.id_tipo_documento', (string) $catalogos['tipoDocumento']->id_tdo)
        ->set('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('reniecTipoMensaje', 'warning')
        ->assertSet('reniecMensaje', 'La consulta a RENIEC no está disponible en este momento. Complete los datos manualmente.')
        ->assertDontSee('URL_API_RENIEC');
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

    AsistentePrueba::personal(test())
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

    AsistentePrueba::personal(test())
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

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $catalogos['pais']->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $catalogos['tipoColegio']->id_tco)
        ->set('generalForm.id_colegio', $catalogos['colegio']->id_col)
        ->assertSet('colegioLabel', '0915389 | AGROPECUARIO PADRE ABAD | UCAYALI / CORONEL PORTILLO / CALLERIA')
        ->set('generalForm.veces_postula_unu', '1')
        ->set('generalForm.veces_postula_otras', '2')
        ->set('generalForm.observacion', 'OBSERVACION GENERAL')
        ->call('guardarPasoGeneral')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 3)
        ->assertSet('generalForm.nombre_colegio_extranjero', '')
        ->assertDispatched('toast', message: 'No hay un proceso de inscripcion activo en este momento. No se puede iniciar una nueva inscripcion.', type: 'warning');
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

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('inscripcionId', 1)
        ->set('pasoActual', 3)
        ->set('generalForm.anio_graduacion', (string) (((int) now()->format('Y')) + 3))
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
        ->assertSet('pasoActual', 3);
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

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('inscripcionId', 1)
        ->set('pasoActual', 3)
        ->set('pagoValidado', true)
        ->set('generalForm.anio_graduacion', '')
        ->call('irPaso', 4)
        ->assertSet('pasoActual', 3)
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
    $colegio = Colegio::query()->with('ubigeo')->firstOrFail();

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
        ->where('codigo_pro', '2026_1')
        ->firstOrFail();

    $procesoOferta = postulacionDePrueba(conAula: true);

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_sede', (string) $procesoOferta->id_sede)
        ->set('postulacionForm.id_carrera', (string) $procesoOferta->id_carrera)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Postulacion guardada. La inscripcion sigue pendiente hasta completar el pago.', type: 'success')
        ->assertRedirect(route('inscripciones.index'));

    $inscripcion = Inscripcion::query()->first();

    expect($inscripcion)->not->toBeNull()
        ->and($inscripcion?->id_persona)->toBe($persona->id_per)
        ->and($inscripcion?->id_carrera)->toBe($procesoOferta->id_carrera)
        ->and($inscripcion?->modalidadAdmision?->codigo_mad)->toBe('EXT')
        ->and($inscripcion?->id_turno)->toBe($procesoOferta->id_turno)
        ->and($inscripcion?->estado_ins?->value)->toBe('PENDIENTE')
        ->and($inscripcion?->veces_postula_unu_ins)->toBe(0)
        ->and($inscripcion?->veces_postula_otras_ins)->toBe(0)
        ->and($inscripcion?->codigo_ins)->not->toStartWith('TMP-')
        ->and($inscripcion?->codigo_ins)->toBe('2610001');

    Carbon\Carbon::setTestNow();
});

test('postulacion is blocked when the proceso has no modalidad for the graduation year', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $persona = crearPersonaParaPostulacion('46027811');
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();
    $postulacion = postulacionDePrueba(conAula: true);

    ProcesoModalidad::query()
        ->where('id_proceso', $postulacion->id_proceso)
        ->whereHas('modalidadAdmision', fn ($query) => $query->where('codigo_mad', 'EXT'))
        ->update(['estado_pmo' => 'DESHABILITADO']);

    $this->actingAs($usuario);

    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('postulacionForm.id_sede', (string) $postulacion->id_sede)
        ->set('postulacionForm.id_carrera', (string) $postulacion->id_carrera)
        ->set('postulacionForm.id_turno', (string) $postulacion->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertSet('pasoActual', 4)
        ->assertDispatched('toast', message: 'El proceso activo no tiene habilitada una modalidad para el año de egreso ingresado. Revise la oferta del proceso.', type: 'warning');

    expect(Inscripcion::query()->count())->toBe(0);

    Carbon\Carbon::setTestNow();
});

test('postulacion assigns the first aula with capacity by configured order', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $persona = crearPersonaParaPostulacion('46027897');
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();
    $proceso = Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail();
    $procesoOferta = postulacionDePrueba();

    $primerAula = crearAulaParaProcesoInscripcion($proceso, $procesoOferta->turno, 'AULA A', 'UAP', 'PISO 1', 2);
    crearAulaParaProcesoInscripcion($proceso, $procesoOferta->turno, 'AULA B', 'UAP', 'PISO 1', 2);

    $this->actingAs($usuario);
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_sede', (string) $procesoOferta->id_sede)
        ->set('postulacionForm.id_carrera', (string) $procesoOferta->id_carrera)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Postulacion guardada. La inscripcion sigue pendiente hasta completar el pago.', type: 'success');

    $inscripcion = Inscripcion::query()->firstOrFail();

    expect($inscripcion->id_proceso_aula)->toBe($primerAula['procesoAula']->id_pau);

    Carbon\Carbon::setTestNow();
});

test('postulacion skips full aulas and assigns the next available aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $personaOcupante = crearPersonaParaPostulacion('46027896');
    $persona = crearPersonaParaPostulacion('46027897');
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();
    $proceso = Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail();
    $procesoOferta = postulacionDePrueba();

    $primerAula = crearAulaParaProcesoInscripcion($proceso, $procesoOferta->turno, 'AULA A', 'UAP', 'PISO 1', 1);
    $segundaAula = crearAulaParaProcesoInscripcion($proceso, $procesoOferta->turno, 'AULA B', 'UAP', 'PISO 1', 2);

    Inscripcion::query()->create([
        'codigo_ins' => 'OCUPADO-001',
        'id_persona' => $personaOcupante->id_per,
        'id_proceso' => $procesoOferta->id_proceso,
        'id_sede' => $procesoOferta->id_sede,
        'id_modalidad_admision' => $procesoOferta->id_modalidad_admision,
        'id_carrera' => $procesoOferta->id_carrera,
        'id_turno' => $procesoOferta->id_turno,
        'id_proceso_aula' => $primerAula['procesoAula']->id_pau,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    $this->actingAs($usuario);
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_sede', (string) $procesoOferta->id_sede)
        ->set('postulacionForm.id_carrera', (string) $procesoOferta->id_carrera)
        ->set('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Postulacion guardada. La inscripcion sigue pendiente hasta completar el pago.', type: 'success');

    $inscripcion = Inscripcion::query()
        ->where('id_persona', $persona->id_per)
        ->firstOrFail();

    expect($inscripcion->id_proceso_aula)->toBe($segundaAula['procesoAula']->id_pau);

    Carbon\Carbon::setTestNow();
});

test('postulacion only assigns aulas from the selected turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    sembrarConfiguracionAcademicaParaInscripcion();

    $persona = crearPersonaParaPostulacion('46027898');
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();
    $tipoColegio = TipoColegio::query()->firstOrFail();
    $colegio = Colegio::query()->firstOrFail();
    $proceso = Proceso::query()->where('codigo_pro', '2026_1')->firstOrFail();

    $procesoOfertaManana = postulacionDePrueba();

    $turnoTarde = Turno::query()
        ->where('id_tur', '!=', $procesoOfertaManana->id_turno)
        ->orderBy('id_tur')
        ->first();

    if ($turnoTarde === null) {
        $turnoTarde = Turno::query()->create([
            'codigo_tur' => 'turno_noche',
            'nombre_tur' => 'NOCHE',
            'estado_tur' => 'HABILITADO',
        ]);
    }

    $procesoOfertaTarde = clone $procesoOfertaManana;
    $procesoOfertaTarde->id_turno = $turnoTarde->id_tur;
    $procesoOfertaTarde->turno = $turnoTarde;

    crearAulaParaProcesoInscripcion($proceso, $procesoOfertaManana->turno, 'AULA MANANA', 'UAP', 'PISO 1', 40);
    $aulaTarde = crearAulaParaProcesoInscripcion($proceso, $procesoOfertaTarde->turno, 'AULA TARDE', 'UAP', 'PISO 2', 50);

    $this->actingAs($usuario);
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2026')
        ->set('generalForm.id_pais_colegio', (string) $pais->id_pai)
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegio->id_tco)
        ->set('generalForm.id_colegio', $colegio->id_col)
        ->set('generalForm.veces_postula_unu', 0)
        ->set('generalForm.veces_postula_otras', 0)
        ->set('postulacionForm.id_sede', (string) $procesoOfertaTarde->id_sede)
        ->set('postulacionForm.id_carrera', (string) $procesoOfertaTarde->id_carrera)
        ->set('postulacionForm.id_turno', (string) $procesoOfertaTarde->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Postulacion guardada. La inscripcion sigue pendiente hasta completar el pago.', type: 'success');

    $inscripcion = Inscripcion::query()->firstOrFail();

    expect($inscripcion->id_proceso_aula)->toBe($aulaTarde['procesoAula']->id_pau);

    Carbon\Carbon::setTestNow();
});

test('changing school type clears selected school in general information form', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $catalogos = crearCatalogosPasoPersonal();

    $tipoColegioPrivado = TipoColegio::query()->create([
        'codigo_tco' => 'PRI',
        'nombre_tco' => 'PRIVADO',
        'estado_tco' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('generalForm.id_tipo_colegio', (string) $catalogos['tipoColegio']->id_tco)
        ->set('generalForm.id_colegio', $catalogos['colegio']->id_col)
        ->assertSet('colegioLabel', '0915389 | AGROPECUARIO PADRE ABAD | UCAYALI / CORONEL PORTILLO / CALLERIA')
        ->set('generalForm.id_tipo_colegio', (string) $tipoColegioPrivado->id_tco)
        ->assertSet('generalForm.id_colegio', '')
        ->assertSet('colegioLabel', '')
        ->assertDispatched('ui-select-search-filled', name: 'generalForm.id_colegio', value: '', label: '');
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
        ->where('codigo_pro', '2026_1')
        ->firstOrFail();

    $procesoOferta = postulacionDePrueba();

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => '26IPRO0001',
        'id_persona' => $persona->id_per,
        'id_proceso' => $procesoOferta->id_proceso,
        'id_sede' => $procesoOferta->id_sede,
        'id_modalidad_admision' => $procesoOferta->id_modalidad_admision,
        'id_carrera' => $procesoOferta->id_carrera,
        'id_turno' => $procesoOferta->id_turno,
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
        ->followingRedirects()
        ->get(route('inscripciones.continuar', encode($inscripcion->id_ins)))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('inscripciones/asistente')->where('asistente.editandoInscripcion', true));

    $ubigeoLabel = "{$ubigeo->id_ubi} | {$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    $colegioLabel = "{$colegio->id_col} | {$colegio->nombre_col} | {$colegio->ubigeo->nombre_departamento_ubi} / {$colegio->ubigeo->nombre_provincia_ubi} / {$colegio->ubigeo->nombre_distrito_ubi}";

    AsistentePrueba::personal(test(), encode($inscripcion->id_ins))
        ->assertSet('inscripcionId', $inscripcion->id_ins)
        ->assertSet('personaId', $persona->id_per)
        ->assertSet('pasoActual', 1)
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
        ->assertSet('postulacionForm.id_sede', (string) $procesoOferta->id_sede)
        ->assertSet('postulacionForm.id_carrera', (string) $procesoOferta->id_carrera)
        ->assertSet('postulacionForm.id_turno', (string) $procesoOferta->id_turno)
        ->assertSet('pagoForm.numero_documento', '46027897')
        ->assertSet('colegioLabel', $colegioLabel);
});

test('new inscripcion page shows blocked state when there is no active process', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);

    $this->actingAs($usuario)
        ->followingRedirects()
        ->get(route('inscripciones.crear'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('asistente.inicioBloqueado', true)
            ->where('asistente.proceso.disponible', false)
            ->where('asistente.proceso.mensaje', fn (string $mensaje): bool => str_contains($mensaje, 'No hay un proceso de inscripcion activo en este momento.')));
});

function crearPagoLibrePrueba(string $dni, float $monto, string $operacion, string $ubicacion = 'RECAUDACIÓN BBVA N° 13248'): ImportacionBancoDetalle
{
    return ImportacionBancoDetalle::query()->create([
        'origen_ibd' => 'MANUAL',
        'numero_documento_ibd' => $dni,
        'numero_operacion_raw_ibd' => $operacion,
        'numero_operacion_visible_ibd' => $operacion,
        'numero_operacion_ibd' => $operacion,
        'fecha_pago_ibd' => '2026-04-14',
        'monto_ibd' => $monto,
        'ubicacion_ibd' => $ubicacion,
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);
}

function crearReglaPagoPrueba(string $codigo, float $total, bool $permiteCuotas = false, float $minimo = 0, ?string $documento = null): ReglaPago
{
    $concepto = ConceptoPago::query()->create([
        'codigo_cpg' => $codigo,
        'nombre_cpg' => "CONCEPTO {$codigo}",
        'permite_cuotas_cpg' => $permiteCuotas,
        'estado_cpg' => 'HABILITADO',
    ]);

    return ReglaPago::query()->create([
        'id_concepto_pago' => $concepto->id_cpg,
        'id_proceso' => Proceso::query()->where('codigo_pro', '2026_1')->value('id_pro'),
        'monto_total_rpa' => $total,
        'permite_cuotas_rpa' => $permiteCuotas,
        'monto_minimo_primera_cuota_rpa' => $minimo,
        'numero_maximo_cuotas_rpa' => $permiteCuotas ? 2 : 1,
        'requiere_pago_completo_rpa' => false,
        'documento_requerido_rpa' => $documento,
        'vigente_desde_rpa' => '2026-04-01',
        'vigente_hasta_rpa' => '2026-04-30',
        'estado_rpa' => 'HABILITADO',
    ]);
}

/**
 * Completa los pasos 2 a 4 con una persona ya registrada y guarda la postulación.
 */
function completarInscripcionDePrueba(AsistentePrueba $componente, Persona $persona): AsistentePrueba
{
    $postulacion = postulacionDePrueba(conAula: true);

    return $componente
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2025')
        ->set('generalForm.id_pais_colegio', (string) Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'))
        ->set('generalForm.id_tipo_colegio', (string) TipoColegio::query()->value('id_tco'))
        ->set('generalForm.id_colegio', Colegio::query()->value('id_col'))
        ->set('postulacionForm.id_sede', (string) $postulacion->id_sede)
        ->set('postulacionForm.id_carrera', (string) $postulacion->id_carrera)
        ->set('postulacionForm.id_turno', (string) $postulacion->id_turno)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion');
}

test('the inscripcion starts by validating a payment and ends active with the payment registered', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $persona = crearPersonaParaPostulacion('46027897');
    $reglaPago = ReglaPago::query()->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', 'INS_CUO'))->firstOrFail();
    $detalle = crearPagoLibrePrueba('46027897', 300, '41676');

    $this->actingAs($usuario);

    $componente = AsistentePrueba::personal(test())
        ->set('pagoForm.numero_documento', '46027897')
        ->set('pagoForm.numero_operacion', '41676')
        ->call('buscarPagosPasoPago')
        ->assertHasNoErrors()
        ->assertSet('resultadoPagoDetalleIds', [$detalle->id_ibd])
        ->set('pagoForm.id_regla_pago', (string) $reglaPago->id_rpa)
        ->call('confirmarPasoPago')
        ->assertHasNoErrors()
        ->assertSet('pagoValidado', true)
        ->assertSet('pasoActual', 2)
        ->assertSet('personaForm.numero_documento', '46027897');

    completarInscripcionDePrueba($componente, $persona)
        ->assertHasNoErrors()
        ->assertRedirect(route('inscripciones.index'));

    $inscripcion = Inscripcion::query()->sole();
    $obligacion = InscripcionObligacionPago::query()->where('id_inscripcion', $inscripcion->id_ins)->sole();

    expect($inscripcion->estado_ins)->toBe(EstadoInscripcion::Activo)
        ->and($obligacion->id_regla_pago)->toBe($reglaPago->id_rpa)
        ->and((float) $obligacion->monto_pagado_iop)->toBe(300.0)
        ->and($obligacion->estado_iop)->toBe(EstadoObligacionPago::Parcial)
        ->and($detalle->fresh()->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Asociado);

    Carbon\Carbon::setTestNow();
});

test('payments of the same dni are added to reach the minimum', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $persona = crearPersonaParaPostulacion('46027898');
    $reglaPago = ReglaPago::query()->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', 'INS_CUO'))->firstOrFail();
    $primero = crearPagoLibrePrueba('46027898', 150, '1001');
    $segundo = crearPagoLibrePrueba('46027898', 125, '1002', 'ADMISIÓN');

    $this->actingAs($usuario);

    $componente = AsistentePrueba::personal(test())
        ->set('pagoForm.numero_documento', '46027898')
        ->set('pagoForm.numero_operacion', '1001')
        ->call('buscarPagosPasoPago')
        ->set('pagoForm.id_regla_pago', (string) $reglaPago->id_rpa)
        ->call('confirmarPasoPago')
        ->assertHasErrors('pagosSeleccionados')
        ->assertSet('pasoActual', 1)
        ->set("pagosSeleccionados.{$segundo->id_ibd}", true)
        ->call('confirmarPasoPago')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2);

    completarInscripcionDePrueba($componente, $persona)->assertRedirect(route('inscripciones.index'));

    $obligacion = InscripcionObligacionPago::query()->sole();

    expect((float) $obligacion->monto_pagado_iop)->toBe(275.0)
        ->and(Inscripcion::query()->sole()->estado_ins)->toBe(EstadoInscripcion::Activo)
        ->and($primero->fresh()->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Asociado)
        ->and($segundo->fresh()->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Asociado);

    Carbon\Carbon::setTestNow();
});

test('a payment that does not exist or is already used cannot start an inscripcion', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    crearPagoLibrePrueba('46027899', 500, '7777')->update(['estado_ibd' => EstadoImportacionBancoDetalle::Asociado]);

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('pagoForm.numero_documento', '46027899')
        ->set('pagoForm.numero_operacion', '7777')
        ->call('buscarPagosPasoPago')
        ->assertHasErrors('pagoForm.numero_operacion')
        ->assertSet('busquedaPagoRealizada', false)
        ->call('irPaso', 2)
        ->assertSet('pasoActual', 1);

    Carbon\Carbon::setTestNow();
});

test('a scholarship asks for the resolution number', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $beca = crearReglaPagoPrueba('BECA_T', 50, documento: 'N° DE RESOLUCIÓN');
    crearPagoLibrePrueba('46027800', 50, '5050');

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('pagoForm.numero_documento', '46027800')
        ->set('pagoForm.numero_operacion', '5050')
        ->call('buscarPagosPasoPago')
        ->set('pagoForm.id_regla_pago', (string) $beca->id_rpa)
        ->call('confirmarPasoPago')
        ->assertHasErrors('pagoForm.numero_documento_beneficio')
        ->set('pagoForm.numero_documento_beneficio', 'R-123-2026')
        ->call('confirmarPasoPago')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2);

    Carbon\Carbon::setTestNow();
});

test('parentesco unu inscribes with a zero payment and keeps the constancia number', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $persona = crearPersonaParaPostulacion('46027801');
    $parentesco = crearReglaPagoPrueba('PAR_T', 0, documento: 'N° DE CONSTANCIA');
    crearPagoLibrePrueba('46027801', 0, '452', 'PARENTESCO UNU');

    $this->actingAs($usuario);

    $componente = AsistentePrueba::personal(test())
        ->set('pagoForm.numero_documento', '46027801')
        ->set('pagoForm.numero_operacion', '452')
        ->call('buscarPagosPasoPago')
        ->set('pagoForm.id_regla_pago', (string) $parentesco->id_rpa)
        ->set('pagoForm.numero_documento_beneficio', '452')
        ->call('confirmarPasoPago')
        ->assertHasNoErrors();

    completarInscripcionDePrueba($componente, $persona)->assertRedirect(route('inscripciones.index'));

    $obligacion = InscripcionObligacionPago::query()->sole();

    expect($obligacion->estado_iop)->toBe(EstadoObligacionPago::Pagado)
        ->and($obligacion->numero_documento_beneficio_iop)->toBe('452')
        ->and(Inscripcion::query()->sole()->estado_ins)->toBe(EstadoInscripcion::Activo);

    Carbon\Carbon::setTestNow();
});

test('confirmarPasoPago is blocked when proceso is no longer active', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();

    Carbon\Carbon::setTestNow('2099-01-01 10:00:00');

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->call('confirmarPasoPago')
        ->assertDispatched('toast', type: 'warning')
        ->assertSet('pasoActual', 1);

    Carbon\Carbon::setTestNow();
});

test('the inscripcion is not registered until the data is confirmed', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionCrear($usuario, ['inscripcion_crear']);
    sembrarConfiguracionAcademicaParaInscripcion();
    Carbon\Carbon::setTestNow('2026-04-15 10:00:00');

    $persona = crearPersonaParaPostulacion('46027802');
    $postulacion = postulacionDePrueba(conAula: true);

    $this->actingAs($usuario);

    AsistentePrueba::personal(test())
        ->set('personaId', $persona->id_per)
        ->set('pasoActual', 4)
        ->set('generalForm.anio_graduacion', '2025')
        ->set('generalForm.id_pais_colegio', (string) Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'))
        ->set('generalForm.id_tipo_colegio', (string) TipoColegio::query()->value('id_tco'))
        ->set('generalForm.id_colegio', Colegio::query()->value('id_col'))
        ->set('postulacionForm.id_sede', (string) $postulacion->id_sede)
        ->set('postulacionForm.id_carrera', (string) $postulacion->id_carrera)
        ->set('postulacionForm.id_turno', (string) $postulacion->id_turno)
        ->call('guardarPasoPostulacion')
        ->assertHasErrors('confirmacionDatos');

    expect(Inscripcion::query()->count())->toBe(0);

    Carbon\Carbon::setTestNow();
});
