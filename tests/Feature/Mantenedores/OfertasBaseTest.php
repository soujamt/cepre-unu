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
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosOfertaBase(Usuario $usuario, array $claves): void
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
 * Sede con un lugar que tiene asignada una modalidad y una carrera.
 *
 * @return array{sede: Sede, lugar: LugarInscripcion, modalidad: ModalidadAdmision, modalidadLugar: ModalidadLugarInscripcion, sedeCarrera: SedeCarrera}
 */
function crearContextoOfertaBase(string $sufijo = '1'): array
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
    $sede = Sede::query()->create([
        'codigo_sed' => "SEDE{$sufijo}",
        'nombre_sed' => "SEDE {$sufijo}",
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);
    $lugar = LugarInscripcion::query()->create([
        'codigo_lin' => "60{$sufijo}",
        'nombre_lin' => "LUGAR {$sufijo}",
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);
    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => "M{$sufijo}",
        'nombre_mad' => "MODALIDAD {$sufijo}",
        'estado_mad' => 'HABILITADO',
    ]);
    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);
    $carrera = Carrera::query()->create([
        'codigo_car' => "CAR{$sufijo}",
        'nombre_car' => "CARRERA {$sufijo}",
        'estado_car' => 'HABILITADO',
    ]);
    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => "SEDE{$sufijo}-CAR{$sufijo}",
        'estado_sca' => 'HABILITADO',
    ]);

    return compact('sede', 'lugar', 'modalidad', 'modalidadLugar', 'sedeCarrera');
}

function crearOfertaBaseDelContexto(array $contexto, string $codigo = '2700'): OfertaBase
{
    return OfertaBase::query()->create([
        'codigo_externo_ofb' => $codigo,
        'id_modalidad_lugar_inscripcion' => $contexto['modalidadLugar']->id_mli,
        'id_sede_carrera' => $contexto['sedeCarrera']->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);
}

function usarOfertaBaseEnProceso(OfertaBase $oferta): void
{
    $proceso = Proceso::query()->create([
        'codigo_pro' => '2026_1',
        'nombre_pro' => '2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);
    $turno = Turno::query()->create([
        'codigo_tur' => 'MAN',
        'nombre_tur' => 'MANANA',
        'estado_tur' => 'HABILITADO',
    ]);

    ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $oferta->id_ofb,
        'id_turno' => $turno->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);
}

test('authorized users can access ofertas base page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice']);

    $this->actingAs($usuario)
        ->get(route('ofertas-base.index'))
        ->assertOk()
        ->assertSee('Ofertas base');
});

test('authenticated users without permiso cannot access ofertas base page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('ofertas-base.index'))
        ->assertForbidden();
});

test('authorized users can register an oferta base', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_crear']);
    $contexto = crearContextoOfertaBase();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->call('cargarOferta')
        ->set('ofertaForm.codigo', ' 3100 ')
        ->set('ofertaForm.id_sede', (string) $contexto['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $contexto['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $contexto['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $contexto['sedeCarrera']->id_sca)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Oferta base registrada correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_oferta_base', [
        'codigo_externo_ofb' => '3100',
        'id_modalidad_lugar_inscripcion' => $contexto['modalidadLugar']->id_mli,
        'id_sede_carrera' => $contexto['sedeCarrera']->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);
});

test('changing the sede in the form clears lugar, modalidad and carrera', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_crear']);
    $contexto = crearContextoOfertaBase('1');
    $otra = crearContextoOfertaBase('2');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->set('ofertaForm.id_sede', (string) $contexto['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $contexto['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $contexto['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $contexto['sedeCarrera']->id_sca)
        ->set('ofertaForm.id_sede', (string) $otra['sede']->id_sed)
        ->assertSet('ofertaForm.id_lugar', '')
        ->assertSet('ofertaForm.id_modalidad', '')
        ->assertSet('ofertaForm.id_sede_carrera', '');
});

test('oferta base rejects a lugar or carrera from another sede and a modalidad not assigned to the lugar', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_crear']);
    $contexto = crearContextoOfertaBase('1');
    $otra = crearContextoOfertaBase('2');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->set('ofertaForm.codigo', '3100')
        ->set('ofertaForm.id_sede', (string) $contexto['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $otra['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $contexto['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $otra['sedeCarrera']->id_sca)
        ->call('guardar')
        ->assertHasErrors(['ofertaForm.id_lugar', 'ofertaForm.id_modalidad', 'ofertaForm.id_sede_carrera']);

    expect(OfertaBase::query()->count())->toBe(0);
});

test('oferta base combination and code must be unique', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_crear']);
    $contexto = crearContextoOfertaBase();
    crearOfertaBaseDelContexto($contexto, '2700');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->set('ofertaForm.codigo', '2700')
        ->set('ofertaForm.id_sede', (string) $contexto['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $contexto['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $contexto['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $contexto['sedeCarrera']->id_sca)
        ->call('guardar')
        ->assertHasErrors(['ofertaForm.codigo', 'ofertaForm.id_sede_carrera']);
});

test('registering a deleted combination restores it', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_crear']);
    $contexto = crearContextoOfertaBase();
    $eliminada = crearOfertaBaseDelContexto($contexto, '2700');
    $eliminada->delete();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->set('ofertaForm.codigo', '2700')
        ->set('ofertaForm.id_sede', (string) $contexto['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $contexto['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $contexto['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $contexto['sedeCarrera']->id_sca)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertNotSoftDeleted('tbl_oferta_base', ['id_ofb' => $eliminada->id_ofb], deletedAtColumn: 'eliminado_en');
    expect(OfertaBase::query()->withTrashed()->count())->toBe(1);
});

test('oferta base used in procesos only allows changing its code', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_editar']);
    $contexto = crearContextoOfertaBase('1');
    $otra = crearContextoOfertaBase('2');
    $oferta = crearOfertaBaseDelContexto($contexto, '2700');
    usarOfertaBaseEnProceso($oferta);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->call('cargarOferta', $oferta->id_ofb)
        ->assertSet('combinacionBloqueada', true)
        ->set('ofertaForm.codigo', '2701')
        ->set('ofertaForm.id_sede', (string) $otra['sede']->id_sed)
        ->set('ofertaForm.id_lugar', (string) $otra['lugar']->id_lin)
        ->set('ofertaForm.id_modalidad', (string) $otra['modalidad']->id_mad)
        ->set('ofertaForm.id_sede_carrera', (string) $otra['sedeCarrera']->id_sca)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_oferta_base', [
        'id_ofb' => $oferta->id_ofb,
        'codigo_externo_ofb' => '2701',
        'id_modalidad_lugar_inscripcion' => $contexto['modalidadLugar']->id_mli,
        'id_sede_carrera' => $contexto['sedeCarrera']->id_sca,
    ]);
});

test('oferta base used in procesos cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_eliminar']);
    $oferta = crearOfertaBaseDelContexto(crearContextoOfertaBase());
    usarOfertaBaseEnProceso($oferta);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->call('eliminar', $oferta->id_ofb)
        ->assertDispatched('toast', message: 'La oferta base se usa en procesos y no puede eliminarse. Puede deshabilitarla.', type: 'error');

    $this->assertNotSoftDeleted('tbl_oferta_base', ['id_ofb' => $oferta->id_ofb], deletedAtColumn: 'eliminado_en');
});

test('authorized users can change state and delete an unused oferta base', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice', 'oferta_base_estado', 'oferta_base_eliminar']);
    $oferta = crearOfertaBaseDelContexto(crearContextoOfertaBase());

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->call('cambiarEstado', $oferta->id_ofb)
        ->assertDispatched('toast', message: 'Estado de la oferta base actualizado.', type: 'success')
        ->call('eliminar', $oferta->id_ofb)
        ->assertDispatched('toast', message: 'Oferta base eliminada correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_oferta_base', ['id_ofb' => $oferta->id_ofb], deletedAtColumn: 'eliminado_en');
});

test('listing can be filtered by sede and modalidad', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaBase($usuario, ['oferta_base_indice']);
    $contexto = crearContextoOfertaBase('1');
    $otra = crearContextoOfertaBase('2');
    crearOfertaBaseDelContexto($contexto, '2700');
    crearOfertaBaseDelContexto($otra, '2800');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.ofertas-base')
        ->set('idSede', (string) $otra['sede']->id_sed)
        ->assertSee('2800')
        ->assertDontSee('2700')
        ->set('idSede', '')
        ->set('idModalidad', (string) $contexto['modalidad']->id_mad)
        ->assertSee('2700')
        ->assertDontSee('2800');
});
