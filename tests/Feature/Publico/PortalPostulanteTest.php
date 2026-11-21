<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Colegio;
use App\Models\EstadoCivil;
use App\Models\FotoPostulante;
use App\Models\IdentidadEtnica;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\TipoColegio;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Carbon\Carbon;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        TurnoSeeder::class, ConceptoPagoSeeder::class, TipoDocumentoSeeder::class, TipoDiscapacidadSeeder::class,
        TipoColegioSeeder::class, EstadoCivilSeeder::class, PaisSeeder::class, NacionalidadSeeder::class,
        UbigeoSeeder::class, IdentidadEtnicaSeeder::class, LenguaNativaSeeder::class, LenguaExtranjeraSeeder::class,
        ColegioSeeder::class, SedeSeeder::class, CarreraSeeder::class, ModalidadAdmisionSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seeder) {
        Artisan::call('db:seed', ['--class' => $seeder, '--no-interaction' => true]);
    }

    Carbon::setTestNow('2026-04-15 10:00:00');
});

afterEach(fn () => Carbon::setTestNow());

function personaDelPortal(string $documento): Persona
{
    $ubigeo = Ubigeo::query()->firstOrFail();

    return Persona::query()->create([
        'id_tipo_documento' => TipoDocumento::query()->where('longitud_minima_tdo', 8)->where('longitud_maxima_tdo', 8)->value('id_tdo'),
        'numero_documento_per' => $documento,
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'DELGADO',
        'apellido_materno_per' => 'HUAMANI',
        'nombres_per' => 'ROXANA',
        'fecha_nacimiento_per' => '2007-02-03',
        'sexo_per' => 'FEMENINO',
        'id_estado_civil' => EstadoCivil::query()->value('id_eci'),
        'id_pais_nacimiento' => Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'),
        'id_nacionalidad' => Nacionalidad::query()->value('id_nac'),
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'direccion_per' => 'JR LOCAL 123',
        'celular_per' => '999111222',
        'correo_electronico_per' => "{$documento}@test.com",
        'nombre_apoderado_per' => 'MARIA HUAMANI',
        'celular_apoderado_per' => '988777666',
        'discapacidad_per' => false,
        'tipo_lengua_materna_per' => 'NATIVA',
        'id_identidad_etnica' => IdentidadEtnica::query()->value('id_iet'),
        'id_lengua_nativa' => LenguaNativa::query()->value('id_lna'),
        'estado_per' => 'HABILITADO',
    ]);
}

function pagoLibreDelPortal(string $documento, float $monto, string $operacion): ImportacionBancoDetalle
{
    return ImportacionBancoDetalle::query()->create([
        'origen_ibd' => 'MANUAL',
        'numero_documento_ibd' => $documento,
        'numero_operacion_raw_ibd' => $operacion,
        'numero_operacion_visible_ibd' => $operacion,
        'numero_operacion_ibd' => $operacion,
        'fecha_pago_ibd' => '2026-04-14',
        'monto_ibd' => $monto,
        'ubicacion_ibd' => 'RECAUDACIÓN BBVA N° 13248',
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);
}

function reglaCuotasDelPortal(): ReglaPago
{
    return ReglaPago::query()->whereHas('conceptoPago', fn ($query) => $query->where('codigo_cpg', 'INS_CUO'))->firstOrFail();
}

function validarPagoEnPortal(string $documento, string $operacion): Testable
{
    return Livewire::test('pages::publico.inscripcion')
        ->set('pagoForm.numero_documento', $documento)
        ->set('pagoForm.numero_operacion', $operacion)
        ->call('buscarPagosPasoPago')
        ->set('pagoForm.id_regla_pago', (string) reglaCuotasDelPortal()->id_rpa)
        ->call('confirmarPasoPago');
}

function completarPortalHastaRegistrar(Testable $componente, Persona $persona): Testable
{
    $procesoCarrera = ProcesoCarrera::query()
        ->whereHas('proceso', fn ($query) => $query->where('codigo_pro', '2026_1'))
        ->orderBy('id_pca')
        ->firstOrFail();
    $turno = Turno::query()->where('codigo_tur', 'MAN')->firstOrFail();
    $aula = Aula::query()->create(['nombre_aul' => 'AULA 101', 'lugar_aul' => 'UAP', 'piso_aul' => 'PISO 1', 'orden_aul' => 1, 'estado_aul' => 'HABILITADO']);
    ProcesoAula::query()->create([
        'id_proceso' => $procesoCarrera->id_proceso,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => 'HABILITADO',
    ]);

    return $componente
        ->set('personaForm.id_tipo_documento', (string) $persona->id_tipo_documento)
        ->call('buscarDatosPersona')
        ->call('irPaso', 3)
        ->set('generalForm.anio_graduacion', '2025')
        ->set('generalForm.id_pais_colegio', (string) Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'))
        ->set('generalForm.id_tipo_colegio', (string) TipoColegio::query()->value('id_tco'))
        ->set('generalForm.id_colegio', Colegio::query()->value('id_col'))
        ->call('guardarPasoGeneral')
        ->set('postulacionForm.id_sede', (string) $procesoCarrera->id_sede)
        ->set('postulacionForm.id_carrera', (string) $procesoCarrera->id_carrera)
        ->set('postulacionForm.id_turno', (string) $turno->id_tur)
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion');
}

test('the portal pages are public', function () {
    $this->get(route('publico.portal'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('publico/portal'));
    $this->get(route('publico.inscripcion'))->assertOk()->assertSee('Validación de pago');
    $this->get(route('publico.consulta'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('publico/consulta'));
});

test('a postulante enrolls online and the inscripcion waits for review', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');

    $componente = validarPagoEnPortal('46027897', '41676')
        ->assertHasNoErrors()
        ->assertSet('pasoActual', 2)
        ->assertSet('personaForm.numero_documento', '46027897')
        ->assertSet('personaId', $persona->id_per);

    completarPortalHastaRegistrar($componente, $persona)
        ->assertHasNoErrors()
        ->assertSee('Inscripción registrada')
        ->assertSee('EN REVISIÓN')
        ->assertSee('Descargar ficha')
        ->assertDontSee('Descargar carnet')
        ->assertSee('AULA 101');

    $inscripcion = Inscripcion::query()->sole();

    expect($inscripcion->estado_ins)->toBe(EstadoInscripcion::Pendiente)
        ->and($inscripcion->origen_ins)->toBe('PORTAL')
        ->and($inscripcion->enRevision())->toBeTrue()
        ->and($inscripcion->id_persona)->toBe($persona->id_per);
});

test('the portal does not let the postulante upload a photo', function () {
    personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');

    validarPagoEnPortal('46027897', '41676')
        ->set('fotoPostulante', UploadedFile::fake()->image('foto.jpg'))
        ->assertForbidden();

    expect(FotoPostulante::query()->count())->toBe(0);
});

test('the wizard state cannot be changed from the browser', function (string $propiedad, mixed $valor) {
    expect(fn () => Livewire::test('pages::publico.inscripcion')->set($propiedad, $valor))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'paso' => ['pasoActual', 4],
    'pago validado' => ['pagoValidado', true],
    'persona' => ['personaId', 1],
    'pagos encontrados' => ['resultadoPagoDetalleIds', [1]],
]);

test('a payment of another dni cannot be marked', function () {
    pagoLibreDelPortal('46027897', 10, '41676');
    $ajeno = pagoLibreDelPortal('70000001', 500, '50000');

    Livewire::test('pages::publico.inscripcion')
        ->set('pagoForm.numero_documento', '46027897')
        ->set('pagoForm.numero_operacion', '41676')
        ->call('buscarPagosPasoPago')
        ->set('pagosSeleccionados', [$ajeno->id_ibd => true])
        ->set('pagoForm.id_regla_pago', (string) reglaCuotasDelPortal()->id_rpa)
        ->call('confirmarPasoPago')
        ->assertHasErrors('pagosSeleccionados')
        ->assertSet('pagoValidado', false);
});

test('after validating the payment the dni stays the one that paid', function () {
    personaDelPortal('70000001');
    pagoLibreDelPortal('46027897', 300, '41676');

    validarPagoEnPortal('46027897', '41676')
        ->set('personaForm.numero_documento', '70000001')
        ->assertSet('personaForm.numero_documento', '46027897')
        ->call('buscarDatosPersona')
        ->assertSet('personaId', null);
});

test('a postulante already enrolled in the process cannot enroll again online', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');
    completarPortalHastaRegistrar(validarPagoEnPortal('46027897', '41676'), $persona);
    pagoLibreDelPortal('46027897', 300, '41677');

    $segunda = validarPagoEnPortal('46027897', '41677')
        ->set('personaForm.id_tipo_documento', (string) $persona->id_tipo_documento)
        ->call('buscarDatosPersona')
        ->call('irPaso', 3)
        ->set('generalForm.anio_graduacion', '2025')
        ->set('generalForm.id_pais_colegio', (string) Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'))
        ->set('generalForm.id_tipo_colegio', (string) TipoColegio::query()->value('id_tco'))
        ->set('generalForm.id_colegio', Colegio::query()->value('id_col'))
        ->call('guardarPasoGeneral')
        ->set('confirmacionDatos', true)
        ->call('guardarPasoPostulacion')
        ->assertHasErrors('confirmacionDatos');

    $segunda->call('confirmarGuardadoDuplicado')->assertForbidden();

    expect(Inscripcion::query()->count())->toBe(1);
});

test('the online lookup shows the aula with dni and operation number', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');
    completarPortalHastaRegistrar(validarPagoEnPortal('46027897', '41676'), $persona);

    $this->postJson(route('publico.consulta.buscar'), ['documento' => '46027897', 'operacion' => '00041676'])
        ->assertOk()
        ->assertJsonPath('codigo', Inscripcion::query()->sole()->codigo_ins)
        ->assertJsonPath('estado', 'EN REVISIÓN')
        ->assertJsonPath('aula.nombre', 'AULA 101')
        ->assertJsonPath('enlaces.carnet', null)
        ->assertJsonPath('enlaces.ficha', fn (string $ficha): bool => str_contains($ficha, 'signature='));

    $this->postJson(route('publico.consulta.buscar'), ['documento' => '46027897', 'operacion' => '99999'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['operacion' => 'No encontramos una inscripción con ese documento y número de operación.']);
});

test('the online lookup validates the document and the operation number', function () {
    $this->postJson(route('publico.consulta.buscar'), ['documento' => '123', 'operacion' => 'ABC'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'documento' => 'El documento debe tener entre 8 y 12 dígitos.',
            'operacion' => 'El número de operación solo tiene dígitos.',
        ]);
});

test('the online lookup allows eight searches per minute from the same ip', function () {
    for ($intento = 0; $intento < 8; $intento++) {
        $this->postJson(route('publico.consulta.buscar'), ['documento' => '46027897', 'operacion' => '99999'])
            ->assertJsonValidationErrors(['operacion' => 'No encontramos una inscripción con ese documento y número de operación.']);
    }

    $this->postJson(route('publico.consulta.buscar'), ['documento' => '46027897', 'operacion' => '99999'])
        ->assertJsonValidationErrors(['operacion' => 'Demasiados intentos. Espere un minuto e intente de nuevo.']);
});

test('public documents only open with a valid signed link', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');
    completarPortalHastaRegistrar(validarPagoEnPortal('46027897', '41676'), $persona);
    $codigo = encode(Inscripcion::query()->sole()->id_ins);

    $this->get(URL::temporarySignedRoute('publico.ficha', now()->addMinutes(5), ['inscripcion' => $codigo]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('publico.ficha', $codigo))->assertForbidden();
    $this->get(route('publico.carnet', $codigo))->assertForbidden();
});

function personalQueRevisa(array $claves): Usuario
{
    $usuario = Usuario::factory()->create();
    $rol = Rol::query()->create(['nombre_rol' => 'ROL '.Str::upper(Str::random(8)), 'descripcion_rol' => 'Pruebas', 'estado_rol' => 'HABILITADO']);
    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accion = Accion::query()->where('clave_accion', Str::afterLast($clave, '_'))->firstOrFail();
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => Str::beforeLast($clave, '_')],
            ['nombre_permiso' => $clave, 'estado_permiso' => 'HABILITADO'],
        );
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );
        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }

    return $usuario;
}

test('staff approves an online inscripcion and it becomes active when the payment reaches the minimum', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');
    completarPortalHastaRegistrar(validarPagoEnPortal('46027897', '41676'), $persona);
    $inscripcion = Inscripcion::query()->sole();
    $usuario = personalQueRevisa(['inscripcion_indice', 'inscripcion_estado']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idProceso', '')
        ->assertSee('En revisión (1)')
        ->call('aprobarRevision', $inscripcion->id_ins)
        ->assertDispatched('toast', message: "Inscripción {$inscripcion->codigo_ins} aprobada.", type: 'success');

    $inscripcion->refresh();

    expect($inscripcion->estado_ins)->toBe(EstadoInscripcion::Activo)
        ->and($inscripcion->enRevision())->toBeFalse()
        ->and($inscripcion->id_usuario_revisor)->toBe($usuario->id_usu);
});

test('users without estado permiso cannot approve online inscripciones', function () {
    $persona = personaDelPortal('46027897');
    pagoLibreDelPortal('46027897', 300, '41676');
    completarPortalHastaRegistrar(validarPagoEnPortal('46027897', '41676'), $persona);
    $inscripcion = Inscripcion::query()->sole();

    $this->actingAs(personalQueRevisa(['inscripcion_indice']));

    Livewire::test('pages::inscripciones.inscripciones')
        ->call('aprobarRevision', $inscripcion->id_ins)
        ->assertForbidden();

    expect($inscripcion->refresh()->enRevision())->toBeTrue();
});
