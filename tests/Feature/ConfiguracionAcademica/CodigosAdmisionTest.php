<?php

use App\Enums\EstadoInscripcion;
use App\Exports\InscripcionesFormatoCepreExport;
use App\Exports\PlantillaCodigosAdmisionExport;
use App\Models\Accion;
use App\Models\Carrera;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoCodigoAdmision;
use App\Models\ProcesoModalidad;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Database\Seeders\PaisSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function otorgarPermisosCodigos(Usuario $usuario, array $claves): void
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

/**
 * Proceso con una sede, dos carreras y la modalidad ORDINARIO habilitadas.
 *
 * @return array{proceso: Proceso, sede: Sede, sistemas: Carrera, derecho: Carrera, ordinario: ModalidadAdmision}
 */
function crearOfertaParaCodigos(): array
{
    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => '250101', 'codigo_departamento_ubi' => '25', 'codigo_provincia_ubi' => '01', 'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI', 'nombre_provincia_ubi' => 'CORONEL PORTILLO', 'nombre_distrito_ubi' => 'CALLERIA', 'estado_ubi' => 'HABILITADO',
    ]);
    $proceso = Proceso::query()->create([
        'codigo_pro' => '2027_2', 'nombre_pro' => '2027-2',
        'fecha_inicio_pro' => '2026-09-01 00:00:00', 'fecha_fin_pro' => '2026-12-31 23:59:59', 'estado_pro' => 'HABILITADO',
    ]);
    $sede = Sede::query()->create(['codigo_sed' => 'SEDPRU', 'nombre_sed' => 'PUCALLPA - CALLERÍA', 'id_ubigeo' => $ubigeo->id_ubi, 'estado_sed' => 'HABILITADO']);
    $sistemas = Carrera::query()->create(['codigo_car' => 'SIS', 'nombre_car' => 'INGENIERÍA DE SISTEMAS', 'estado_car' => 'HABILITADO']);
    $derecho = Carrera::query()->create(['codigo_car' => 'DER', 'nombre_car' => 'DERECHO', 'estado_car' => 'HABILITADO']);
    $ordinario = ModalidadAdmision::query()->where('codigo_mad', 'ORD')->firstOrFail();

    ProcesoModalidad::query()->create(['id_proceso' => $proceso->id_pro, 'id_modalidad_admision' => $ordinario->id_mad, 'estado_pmo' => 'HABILITADO']);

    foreach ([$sistemas, $derecho] as $carrera) {
        ProcesoCarrera::query()->create(['id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_carrera' => $carrera->id_car, 'estado_pca' => 'HABILITADO']);
    }

    return compact('proceso', 'sede', 'sistemas', 'derecho', 'ordinario');
}

/**
 * @param  list<list<string|null>>  $filas
 */
function plantillaLlena(array $filas): UploadedFile
{
    $libro = new Spreadsheet;
    $libro->getActiveSheet()->fromArray([['CLAVE', 'TIPO', 'SEDE', 'MODALIDAD', 'CARRERA', 'CODIGO_ADMISION'], ...$filas]);
    $ruta = tempnam(sys_get_temp_dir(), 'codigos').'.xlsx';
    (new Xlsx($libro))->save($ruta);

    return UploadedFile::fake()->createWithContent('codigos.xlsx', (string) file_get_contents($ruta));
}

test('authorized users see one lugar row and one row per carrera', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCodigos($usuario, ['codigo_admision_indice']);
    crearOfertaParaCodigos();

    $this->actingAs($usuario)
        ->get(route('codigos-admision.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('configuracion-academica/codigos-admision')
            ->has('filas', 3)
            ->where('filas.0.esLugar', true)
            ->where('filas.0.sede', 'PUCALLPA - CALLERÍA')
            ->where('filas.0.modalidad', 'ORDINARIO')
            ->where('filas', fn ($filas): bool => collect($filas)->contains('carrera', 'INGENIERÍA DE SISTEMAS'))
            ->where('avance', ['total' => 3, 'conCodigo' => 0]));
});

test('users without permiso cannot see the codes page', function () {
    $this->actingAs(Usuario::factory()->create())->get(route('codigos-admision.index'))->assertForbidden();
});

test('codes are saved by hand and an empty code removes it', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCodigos($usuario, ['codigo_admision_indice', 'codigo_admision_editar']);
    ['sede' => $sede, 'sistemas' => $sistemas, 'ordinario' => $ordinario] = crearOfertaParaCodigos();
    $claveLugar = ProcesoCodigoAdmision::clave($sede->id_sed, $ordinario->id_mad);
    $claveSistemas = ProcesoCodigoAdmision::clave($sede->id_sed, $ordinario->id_mad, $sistemas->id_car);

    $proceso = Proceso::query()->firstOrFail();

    $this->actingAs($usuario)
        ->put(route('codigos-admision.update', $proceso), ['codigos' => [$claveLugar => '603', $claveSistemas => ' 2700 ']])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Códigos guardados: 2.']);

    expect(ProcesoCodigoAdmision::query()->whereNull('id_carrera')->value('codigo_pcd'))->toBe('603')
        ->and(ProcesoCodigoAdmision::query()->where('id_carrera', $sistemas->id_car)->value('codigo_pcd'))->toBe('2700');

    $this->actingAs($usuario)->put(route('codigos-admision.update', $proceso), ['codigos' => [$claveLugar => '603', $claveSistemas => '']]);

    expect(ProcesoCodigoAdmision::withTrashed()->count())->toBe(1);
});

test('users without editar permiso cannot save codes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCodigos($usuario, ['codigo_admision_indice']);
    crearOfertaParaCodigos();

    $this->actingAs($usuario)
        ->put(route('codigos-admision.update', Proceso::query()->firstOrFail()), ['codigos' => []])
        ->assertForbidden();
});

test('the template lists every combination of the process with an empty code column', function () {
    Excel::fake();
    $usuario = Usuario::factory()->create();
    otorgarPermisosCodigos($usuario, ['codigo_admision_indice']);
    ['sede' => $sede, 'ordinario' => $ordinario] = crearOfertaParaCodigos();

    $this->actingAs($usuario)->get(route('codigos-admision.plantilla', Proceso::query()->firstOrFail()));

    Excel::assertDownloaded('codigos-admision-2027-2.xlsx', function (PlantillaCodigosAdmisionExport $export) use ($sede, $ordinario): bool {
        $filas = $export->array();

        return count($filas) === 3
            && $filas[0] === [ProcesoCodigoAdmision::clave($sede->id_sed, $ordinario->id_mad), 'LUGAR', 'PUCALLPA - CALLERÍA', 'ORDINARIO', null, null];
    });
});

test('the filled template saves its codes and reports unknown rows', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCodigos($usuario, ['codigo_admision_indice', 'codigo_admision_editar']);
    ['sede' => $sede, 'derecho' => $derecho, 'ordinario' => $ordinario] = crearOfertaParaCodigos();

    $proceso = Proceso::query()->firstOrFail();

    $this->actingAs($usuario)
        ->post(route('codigos-admision.cargar-plantilla', $proceso), ['plantilla' => plantillaLlena([
            [ProcesoCodigoAdmision::clave($sede->id_sed, $ordinario->id_mad), 'LUGAR', null, null, null, '603'],
            [ProcesoCodigoAdmision::clave($sede->id_sed, $ordinario->id_mad, $derecho->id_car), 'OFERTA', null, null, null, '2705'],
            ['99-99', 'LUGAR', null, null, null, '700'],
        ])])
        ->assertSessionHas('erroresPlantilla', ['Fila 4: la clave "99-99" no corresponde a la oferta de este proceso.'])
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'Plantilla cargada: 2 código(s) guardado(s).']);

    $this->actingAs($usuario)
        ->get(route('codigos-admision.index', ['proceso' => $proceso->id_pro]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('avance', ['total' => 3, 'conCodigo' => 2]));

    expect(ProcesoCodigoAdmision::query()->pluck('codigo_pcd')->all())->toEqualCanonicalizing(['603', '2705']);
});

test('the inscritos export uses the admision codes and falls back to names', function () {
    Artisan::call('db:seed', ['--class' => TipoDocumentoSeeder::class]);
    Artisan::call('db:seed', ['--class' => PaisSeeder::class]);
    ['proceso' => $proceso, 'sede' => $sede, 'sistemas' => $sistemas, 'derecho' => $derecho, 'ordinario' => $ordinario] = crearOfertaParaCodigos();

    ProcesoCodigoAdmision::query()->create(['id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_modalidad_admision' => $ordinario->id_mad, 'codigo_pcd' => '603']);
    ProcesoCodigoAdmision::query()->create(['id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_modalidad_admision' => $ordinario->id_mad, 'id_carrera' => $sistemas->id_car, 'codigo_pcd' => '2700']);

    $inscripciones = collect([$sistemas, $derecho])->map(function (Carrera $carrera, int $indice) use ($proceso, $sede, $ordinario): Inscripcion {
        $persona = Persona::query()->create([
            'id_tipo_documento' => TipoDocumento::query()->value('id_tdo'),
            'numero_documento_per' => '4602789'.$indice,
            'solo_un_apellido_per' => false,
            'apellido_paterno_per' => 'RUIZ',
            'nombres_per' => 'ANA',
            'id_pais_nacimiento' => Pais::query()->value('id_pai'),
            'estado_per' => 'HABILITADO',
        ]);

        return Inscripcion::query()->create([
            'codigo_ins' => 'INS-'.$indice,
            'id_persona' => $persona->id_per,
            'id_proceso' => $proceso->id_pro,
            'id_sede' => $sede->id_sed,
            'id_modalidad_admision' => $ordinario->id_mad,
            'id_carrera' => $carrera->id_car,
            'fecha_inscripcion_ins' => now(),
            'estado_ins' => EstadoInscripcion::Activo->value,
        ])->load(['persona', 'sede', 'modalidadAdmision', 'carrera']);
    });

    $filas = (new InscripcionesFormatoCepreExport($inscripciones))->collection();

    expect([$filas[0][33], $filas[0][34]])->toBe(['603', '2700'])
        ->and([$filas[1][33], $filas[1][34]])->toBe(['603', 'DERECHO']);
});
