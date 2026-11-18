<?php

use App\Models\Accion;
use App\Models\Carrera;
use App\Models\ModalidadAdmision;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosOfertaProceso(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $permisoClave = Str::beforeLast($clave, '_');
        $accion = Accion::query()->where('clave_accion', Str::afterLast($clave, '_'))->firstOrFail();

        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $permisoClave],
            ['nombre_permiso' => Str::upper($permisoClave), 'estado_permiso' => 'HABILITADO'],
        );

        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

/**
 * @return array{proceso: Proceso, sede: Sede, carreras: list<Carrera>, ordinario: ModalidadAdmision, reserva: ModalidadAdmision}
 */
function crearContextoOfertaProceso(): array
{
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

    $proceso = Proceso::query()->create([
        'codigo_pro' => '2027_2',
        'nombre_pro' => '2027-2',
        'fecha_inicio_pro' => '2026-09-01 00:00:00',
        'fecha_fin_pro' => '2026-12-31 23:59:59',
        'estado_pro' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => 'SEDPRU',
        'nombre_sed' => 'PUCALLPA - CALLERÍA',
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $carreras = [
        Carrera::query()->create(['codigo_car' => 'SIS', 'nombre_car' => 'INGENIERÍA DE SISTEMAS', 'estado_car' => 'HABILITADO']),
        Carrera::query()->create(['codigo_car' => 'DER', 'nombre_car' => 'DERECHO', 'estado_car' => 'HABILITADO']),
    ];

    return [
        'proceso' => $proceso,
        'sede' => $sede,
        'carreras' => $carreras,
        'ordinario' => ModalidadAdmision::query()->where('codigo_mad', 'ORD')->firstOrFail(),
        'reserva' => ModalidadAdmision::query()->where('codigo_mad', 'EXT')->firstOrFail(),
    ];
}

test('authorized users can access oferta del proceso page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaProceso($usuario, ['oferta_indice']);
    crearContextoOfertaProceso();

    $this->actingAs($usuario)
        ->get(route('ofertas.index'))
        ->assertOk()
        ->assertSee('Oferta del proceso')
        ->assertSee('EXTRAORDINARIO O RESERVA')
        ->assertSee('PUCALLPA - CALLERÍA');
});

test('users without permiso cannot access oferta del proceso page', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('ofertas.index'))
        ->assertForbidden();
});

test('authorized users can enable modalidades and carreras per sede for a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaProceso($usuario, ['oferta_indice', 'oferta_editar']);
    $contexto = crearContextoOfertaProceso();
    [$sistemas, $derecho] = $contexto['carreras'];

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->assertSet('idProceso', (string) $contexto['proceso']->id_pro)
        ->set("modalidadesSeleccionadas.{$contexto['ordinario']->id_mad}", true)
        ->set("modalidadesSeleccionadas.{$contexto['reserva']->id_mad}", true)
        ->set("carrerasSeleccionadas.{$contexto['sede']->id_sed}.{$sistemas->id_car}", true)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Oferta del proceso guardada correctamente.', type: 'success');

    expect(ProcesoModalidad::query()->where('id_proceso', $contexto['proceso']->id_pro)->where('estado_pmo', 'HABILITADO')->count())->toBe(2)
        ->and(ProcesoCarrera::query()->where('id_proceso', $contexto['proceso']->id_pro)->where('id_carrera', $sistemas->id_car)->value('estado_pca')?->value)->toBe('HABILITADO')
        ->and(ProcesoCarrera::query()->where('id_carrera', $derecho->id_car)->exists())->toBeFalse();
});

test('unchecking a carrera disables it without deleting the record', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaProceso($usuario, ['oferta_indice', 'oferta_editar']);
    $contexto = crearContextoOfertaProceso();
    [$sistemas] = $contexto['carreras'];

    ProcesoModalidad::query()->create([
        'id_proceso' => $contexto['proceso']->id_pro,
        'id_modalidad_admision' => $contexto['ordinario']->id_mad,
        'estado_pmo' => 'HABILITADO',
    ]);
    ProcesoCarrera::query()->create([
        'id_proceso' => $contexto['proceso']->id_pro,
        'id_sede' => $contexto['sede']->id_sed,
        'id_carrera' => $sistemas->id_car,
        'estado_pca' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->assertSet("carrerasSeleccionadas.{$contexto['sede']->id_sed}.{$sistemas->id_car}", true)
        ->call('quitarTodasLasCarreras', $contexto['sede']->id_sed)
        ->call('guardar')
        ->assertHasNoErrors();

    expect(ProcesoCarrera::query()->where('id_carrera', $sistemas->id_car)->value('estado_pca')?->value)->toBe('DESHABILITADO');
});

test('oferta del proceso requires at least one modalidad', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaProceso($usuario, ['oferta_indice', 'oferta_editar']);
    crearContextoOfertaProceso();

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->call('guardar')
        ->assertHasErrors('modalidadesSeleccionadas');

    expect(ProcesoModalidad::query()->count())->toBe(0);
});

test('users without editar permiso cannot save the oferta', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosOfertaProceso($usuario, ['oferta_indice']);
    $contexto = crearContextoOfertaProceso();

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.ofertas')
        ->set("modalidadesSeleccionadas.{$contexto['ordinario']->id_mad}", true)
        ->call('guardar')
        ->assertForbidden();
});
