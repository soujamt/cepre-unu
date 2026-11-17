<?php

use App\Enums\EstadoInscripcion;
use App\Models\Accion;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\ProcesoCarrera;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Usuario;
use App\Support\ProcesadorFotoPostulante;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    foreach ([
        TurnoSeeder::class,
        TipoDocumentoSeeder::class,
        ConceptoPagoSeeder::class,
        PaisSeeder::class,
        UbigeoSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        ModalidadAdmisionSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seeder) {
        Artisan::call('db:seed', ['--class' => $seeder, '--no-interaction' => true]);
    }
});

function otorgarPermisosDocumentos(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accion = Accion::query()->where('clave_accion', Str::afterLast($clave, '_'))->firstOrFail();
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => Str::beforeLast($clave, '_')],
            ['nombre_permiso' => Str::upper($clave), 'estado_permiso' => 'HABILITADO'],
        );
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

function crearInscripcionDocumento(EstadoInscripcion $estado, string $documento = '47940211'): Inscripcion
{
    $procesoCarrera = ProcesoCarrera::query()->orderBy('id_pca')->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => TipoDocumento::query()->where('longitud_minima_tdo', 8)->value('id_tdo'),
        'numero_documento_per' => $documento,
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'RUIZ',
        'apellido_materno_per' => 'LOPEZ',
        'nombres_per' => 'MARIA',
        'id_pais_nacimiento' => Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->value('id_pai'),
        'estado_per' => 'HABILITADO',
    ]);

    return Inscripcion::query()->create([
        'codigo_ins' => 'INS-'.$documento,
        'id_persona' => $persona->id_per,
        'id_proceso' => $procesoCarrera->id_proceso,
        'id_sede' => $procesoCarrera->id_sede,
        'id_modalidad_admision' => ModalidadAdmision::query()->where('codigo_mad', 'ORD')->value('id_mad'),
        'id_carrera' => $procesoCarrera->id_carrera,
        'id_turno' => Turno::query()->where('codigo_tur', 'MAN')->value('id_tur'),
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => $estado->value,
    ]);
}

/**
 * Captura los datos con los que se arma la vista del PDF.
 *
 * @return ArrayObject<string, mixed>
 */
function capturarVistaPdf(string $vista): ArrayObject
{
    $datos = new ArrayObject;

    View::composer($vista, function ($view) use ($datos): void {
        $datos->exchangeArray($view->getData());
    });

    return $datos;
}

test('every inscripcion gets an unguessable verification token', function () {
    $primera = crearInscripcionDocumento(EstadoInscripcion::Activo);
    $segunda = crearInscripcionDocumento(EstadoInscripcion::Activo, '61417851');

    expect($primera->token_verificacion_ins)->toHaveLength(40)
        ->and($primera->token_verificacion_ins)->not->toBe($segunda->token_verificacion_ins);
});

test('the ficha de matricula is a pdf with the postulante data and photo', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_indice']);
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Pendiente);
    app(ProcesadorFotoPostulante::class)->guardar(UploadedFile::fake()->image('foto.jpg', 300, 400), '47940211');
    $datos = capturarVistaPdf('pdf.ficha-matricula');

    $this->actingAs($usuario)
        ->get(route('inscripciones.ficha', encode($inscripcion->id_ins)))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($datos['inscripcion']->is($inscripcion))->toBeTrue()
        ->and($datos['persona']->numero_documento_per)->toBe('47940211')
        ->and($datos['foto'])->toStartWith('data:image/jpeg;base64,');
});

test('the carnet of an active inscripcion carries its data and qr', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_indice']);
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Activo);
    $datos = capturarVistaPdf('pdf.carnet');

    $this->actingAs($usuario)
        ->get(route('inscripciones.carnet', encode($inscripcion->id_ins)))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($datos['carnets'])->toHaveCount(1)
        ->and($datos['carnets'][0])->toMatchArray([
            'documento' => 'DNI N° 47940211',
            'apellidos' => 'RUIZ LOPEZ',
            'nombres' => 'MARIA',
            'foto' => null,
        ])
        ->and($datos['carnets'][0]['qr'])->toStartWith('data:image/svg+xml;base64,');
});

test('a pending inscripcion has no carnet', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_indice']);
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Pendiente);

    $this->actingAs($usuario)
        ->get(route('inscripciones.carnet', encode($inscripcion->id_ins)))
        ->assertStatus(409);
});

test('ficha and carnet require the inscripcion indice permiso', function () {
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Activo);

    $this->actingAs(Usuario::factory()->create())
        ->get(route('inscripciones.ficha', encode($inscripcion->id_ins)))
        ->assertForbidden();

    $this->actingAs(Usuario::factory()->create())
        ->get(route('inscripciones.carnet', encode($inscripcion->id_ins)))
        ->assertForbidden();
});

test('batch carnets only include active inscripciones of the filtered list', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_indice']);
    crearInscripcionDocumento(EstadoInscripcion::Activo, '47940211');
    crearInscripcionDocumento(EstadoInscripcion::Pendiente, '61417851');
    crearInscripcionDocumento(EstadoInscripcion::Anulado, '70000001');
    $datos = capturarVistaPdf('pdf.carnet');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idProceso', '')
        ->call('generarCarnets')
        ->assertFileDownloaded();

    expect(collect($datos['carnets'])->pluck('documento')->all())->toBe(['DNI N° 47940211']);
});

test('batch carnets warn when the filtered list has no active inscripciones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_indice']);
    crearInscripcionDocumento(EstadoInscripcion::Pendiente);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idProceso', '')
        ->call('generarCarnets')
        ->assertNoFileDownloaded()
        ->assertDispatched('toast', message: 'No hay inscripciones ACTIVAS con los filtros actuales.', type: 'warning');
});

test('the public verification page shows a valid carnet with a masked dni', function () {
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Activo);

    $this->get(route('carnet.verificar', $inscripcion->token_verificacion_ins))
        ->assertOk()
        ->assertSee('CARNET VÁLIDO')
        ->assertSee('MARIA RUIZ LOPEZ')
        ->assertSee('****0211')
        ->assertDontSee('47940211');
});

test('the public verification page marks a cancelled inscripcion as not valid', function () {
    $inscripcion = crearInscripcionDocumento(EstadoInscripcion::Anulado);

    $this->get(route('carnet.verificar', $inscripcion->token_verificacion_ins))
        ->assertOk()
        ->assertSee('CARNET NO VÁLIDO');
});

test('an unknown verification token returns not found', function () {
    $this->get(route('carnet.verificar', Str::random(40)))->assertNotFound();
});

test('the revision step shows the postulante photo and lets the operator upload one', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDocumentos($usuario, ['inscripcion_crear']);

    $this->actingAs($usuario);

    $componente = Livewire::test('pages::inscripciones.inscripcion-crear')
        ->set('personaForm.numero_documento', '47940211');

    expect($componente->get('fotoPostulanteActual'))->toBeNull();

    $componente->set('fotoPostulante', UploadedFile::fake()->image('foto.png', 500, 500))
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Foto del postulante actualizada.', type: 'success');

    expect(FotoPostulante::deDocumento('47940211'))->not->toBeNull();
});
