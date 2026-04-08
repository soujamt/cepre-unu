<?php

use App\Models\Accion;
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
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ->set('personaForm.solo_un_apellido', '0')
        ->set('personaForm.tiene_apellido_casada', '0')
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
        ->set('personaForm.discapacidad', '1')
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
