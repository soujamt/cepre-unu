<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\OfertaBase;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Semestre;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosOferta(Usuario $usuario, array $claves): void
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

/**
 * @return array<string, mixed>
 */
function crearContextoOfertaAcademica(int $indice): array
{
    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => sprintf('25010%d', $indice),
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => sprintf('%02d', $indice),
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => "DISTRITO {$indice}",
        'estado_ubi' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => "SEDE{$indice}",
        'nombre_sed' => "SEDE {$indice}",
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $lugar = LugarInscripcion::query()->create([
        'codigo_lin' => "LUGAR{$indice}",
        'nombre_lin' => "LUGAR {$indice}",
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);

    $carrera = Carrera::query()->create([
        'codigo_car' => "CARRERA{$indice}",
        'nombre_car' => "CARRERA {$indice}",
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => "SCA{$indice}",
        'estado_sca' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => "MAD{$indice}",
        'nombre_mad' => "MODALIDAD {$indice}",
        'estado_mad' => 'HABILITADO',
    ]);
    $modalidadLugarInscripcion = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $ofertaBase = OfertaBase::query()->create([
        'codigo_externo_ofb' => (string) (2700 + $indice),
        'id_modalidad_lugar_inscripcion' => $modalidadLugarInscripcion->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "TUR{$indice}",
        'nombre_tur' => "TURNO {$indice}",
        'estado_tur' => 'HABILITADO',
    ]);

    return [
        'ofertaBase' => $ofertaBase,
        'modalidad' => $modalidad,
        'lugarInscripcion' => $lugar,
        'turno' => $turno,
    ];
}

function crearOfertaBaseAdicionalParaContexto(array $contexto, int $indice): OfertaBase
{
    $sede = $contexto['lugarInscripcion']->sede;

    $carrera = Carrera::query()->create([
        'codigo_car' => "CARRERA_EXTRA{$indice}",
        'nombre_car' => "CARRERA EXTRA {$indice}",
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => "SCA_EXTRA{$indice}",
        'estado_sca' => 'HABILITADO',
    ]);

    return OfertaBase::query()->create([
        'codigo_externo_ofb' => (string) (3700 + $indice),
        'id_modalidad_lugar_inscripcion' => ModalidadLugarInscripcion::query()
            ->where('id_modalidad_admision', $contexto['modalidad']->id_mad)
            ->where('id_lugar_inscripcion', $contexto['lugarInscripcion']->id_lin)
            ->value('id_mli'),
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);
}

test('authorized users can access ofertas page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_indice']);

    $this->actingAs($usuario)
        ->get(route('ofertas.index'))
        ->assertOk()
        ->assertSee('OFERTAS');
});

test('authorized users can access asignar ofertas page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    $this->actingAs($usuario)
        ->get(route('ofertas.asignar'))
        ->assertOk()
        ->assertSee('ASIGNAR OFERTAS');
});

test('users can filter ofertas base by modalidad and lugar de inscripcion', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    $contextoA = crearContextoOfertaAcademica(1);
    $contextoB = crearContextoOfertaAcademica(2);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->set('idModalidad', (string) $contextoA['modalidad']->id_mad)
        ->set('idLugarInscripcion', (string) $contextoA['lugarInscripcion']->id_lin)
        ->assertSee((string) $contextoA['ofertaBase']->codigo_externo_ofb)
        ->assertDontSee((string) $contextoB['ofertaBase']->codigo_externo_ofb)
        ->set('idModalidad', '')
        ->set('idLugarInscripcion', (string) $contextoB['lugarInscripcion']->id_lin)
        ->assertDontSee((string) $contextoB['ofertaBase']->codigo_externo_ofb)
        ->set('idModalidad', (string) $contextoB['modalidad']->id_mad)
        ->set('idLugarInscripcion', (string) $contextoB['lugarInscripcion']->id_lin)
        ->assertSee((string) $contextoB['ofertaBase']->codigo_externo_ofb)
        ->assertDontSee((string) $contextoA['ofertaBase']->codigo_externo_ofb);
});

test('asignar ofertas page waits for modalidad and lugar before showing results', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    crearContextoOfertaAcademica(1);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->assertSee('Seleccione modalidad y lugar de inscripcion para ver las ofertas disponibles.')
        ->assertDontSee('2701');
});

test('users can select all visible ofertas base except already assigned ones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $contexto = crearContextoOfertaAcademica(1);
    $ofertaExtra = crearOfertaBaseAdicionalParaContexto($contexto, 1);

    ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contexto['ofertaBase']->id_ofb,
        'id_turno' => $contexto['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->set('idProceso', (string) $proceso->id_pro)
        ->set('idTurno', (string) $contexto['turno']->id_tur)
        ->set('idModalidad', (string) $contexto['modalidad']->id_mad)
        ->set('idLugarInscripcion', (string) $contexto['lugarInscripcion']->id_lin)
        ->call('seleccionarTodasLasOfertasVisibles')
        ->assertSet('seleccionadas', [(string) $ofertaExtra->id_ofb]);
});

test('authenticated users without permiso cannot access ofertas page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('ofertas.index'))
        ->assertForbidden();
});

test('authorized users can register multiple ofertas por proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $contextoA = crearContextoOfertaAcademica(1);
    $contextoB = crearContextoOfertaAcademica(2);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->set('idProceso', (string) $proceso->id_pro)
        ->set('idTurno', (string) $contextoA['turno']->id_tur)
        ->set('idModalidad', (string) $contextoA['modalidad']->id_mad)
        ->set('idLugarInscripcion', (string) $contextoA['lugarInscripcion']->id_lin)
        ->set('seleccionadas', [(string) $contextoA['ofertaBase']->id_ofb, (string) $contextoB['ofertaBase']->id_ofb])
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_proceso_oferta', [
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contextoA['ofertaBase']->id_ofb,
        'id_turno' => $contextoA['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $this->assertDatabaseHas('tbl_proceso_oferta', [
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contextoB['ofertaBase']->id_ofb,
        'id_turno' => $contextoA['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update an oferta por proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_indice', 'oferta_editar']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $procesoA = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $procesoB = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_extraordinario_2026_i',
        'nombre_pro' => 'PROCESO EXTRAORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-02-01',
        'fecha_fin_pro' => '2026-03-20',
        'estado_pro' => 'HABILITADO',
    ]);

    $contextoA = crearContextoOfertaAcademica(1);
    $contextoB = crearContextoOfertaAcademica(2);

    $oferta = ProcesoOferta::query()->create([
        'id_proceso' => $procesoA->id_pro,
        'id_oferta_base' => $contextoA['ofertaBase']->id_ofb,
        'id_turno' => $contextoA['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->call('cargarOferta', $oferta->id_pof)
        ->set('ofertaForm.id_proceso', (string) $procesoB->id_pro)
        ->set('ofertaForm.id_oferta_base', (string) $contextoB['ofertaBase']->id_ofb)
        ->set('ofertaForm.id_turno', (string) $contextoB['turno']->id_tur)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_proceso_oferta', [
        'id_pof' => $oferta->id_pof,
        'id_proceso' => $procesoB->id_pro,
        'id_oferta_base' => $contextoB['ofertaBase']->id_ofb,
        'id_turno' => $contextoB['turno']->id_tur,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of an oferta por proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_indice', 'oferta_estado']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $contexto = crearContextoOfertaAcademica(1);

    $oferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contexto['ofertaBase']->id_ofb,
        'id_turno' => $contexto['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->call('cambiarEstado', $oferta->id_pof)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_proceso_oferta', [
        'id_pof' => $oferta->id_pof,
        'estado_pof' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete an oferta por proceso without inscripciones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_indice', 'oferta_eliminar']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $contexto = crearContextoOfertaAcademica(1);

    $oferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contexto['ofertaBase']->id_ofb,
        'id_turno' => $contexto['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->call('eliminar', $oferta->id_pof)
        ->assertHasNoErrors();

    $ofertaEliminada = ProcesoOferta::withTrashed()->find($oferta->id_pof);

    expect($ofertaEliminada)->not->toBeNull();
    expect($ofertaEliminada?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('oferta validates duplicate combination and required fields', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOferta($usuario, ['oferta_crear']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $contexto = crearContextoOfertaAcademica(1);

    ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $contexto['ofertaBase']->id_ofb,
        'id_turno' => $contexto['turno']->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->set('idProceso', (string) $proceso->id_pro)
        ->set('idTurno', (string) $contexto['turno']->id_tur)
        ->set('idModalidad', (string) $contexto['modalidad']->id_mad)
        ->set('idLugarInscripcion', (string) $contexto['lugarInscripcion']->id_lin)
        ->set('seleccionadas', [(string) $contexto['ofertaBase']->id_ofb])
        ->call('guardar')
        ->assertHasNoErrors();

    expect(ProcesoOferta::query()
        ->where('id_proceso', $proceso->id_pro)
        ->where('id_turno', $contexto['turno']->id_tur)
        ->count())->toBe(1);

    Livewire::test('pages::configuracion-academica.ofertas-asignar')
        ->set('idProceso', '')
        ->set('idTurno', '')
        ->set('idModalidad', '')
        ->set('idLugarInscripcion', '')
        ->set('seleccionadas', [])
        ->call('guardar')
        ->assertHasErrors([
            'idProceso',
            'idTurno',
            'idModalidad',
            'idLugarInscripcion',
            'seleccionadas',
        ]);
});
