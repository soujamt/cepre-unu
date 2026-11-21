<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\Rol;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-02-01 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function otorgarPermisosProceso(Usuario $usuario, array $claves): void
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

test('authorized users can access procesos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice']);

    $this->actingAs($usuario)
        ->get(route('procesos.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('configuracion-academica/procesos')->has('procesos.data'));
});

test('authenticated users without permiso cannot access procesos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('procesos.index'))
        ->assertForbidden();
});

test('authorized users can register a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_crear']);

    $this->actingAs($usuario)
        ->post(route('procesos.store'), ['nombre' => '2026-2', 'fecha_inicio' => '2026-01-10T08:00', 'fecha_fin' => '2026-03-15T18:30', 'anio_egreso_max_ordinario' => ''])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Proceso registrado correctamente.']);

    $this->assertDatabaseHas('tbl_proceso', [
        'codigo_pro' => '2026_2',
        'nombre_pro' => '2026-2',
        'fecha_inicio_pro' => '2026-01-10 08:00:00',
        'fecha_fin_pro' => '2026-03-15 18:30:00',
        'estado_pro' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_editar']);

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->put(route('procesos.update', $proceso), ['nombre' => '2026-3', 'fecha_inicio' => '2026-04-10', 'fecha_fin' => '2026-07-15', 'anio_egreso_max_ordinario' => '2025'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Proceso actualizado correctamente.');

    expect($proceso->fresh()->anio_egreso_max_ordinario_pro)->toBe(2025);

    $this->assertDatabaseHas('tbl_proceso', [
        'id_pro' => $proceso->id_pro,
        'codigo_pro' => '2026_3',
        'nombre_pro' => '2026-3',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_estado']);

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->patch(route('procesos.estado', $proceso))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del proceso actualizado.']);

    $this->assertDatabaseHas('tbl_proceso', [
        'id_pro' => $proceso->id_pro,
        'estado_pro' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('closed procesos are listed for historical queries', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice']);

    $procesoCerrado = Proceso::query()->create([
        'codigo_pro' => '2026_1',
        'nombre_pro' => '2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'CERRADO',
    ]);

    $this->actingAs($usuario)
        ->get(route('procesos.index', ['estado' => ['CERRADO']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('procesos.data', 1)
            ->where('procesos.data.0.nombre', $procesoCerrado->nombre_pro)
            ->where('procesos.data.0.estado', 'CERRADO')
            ->where('filtros.estado', ['CERRADO']));
});

test('expired proceso is synchronized as cerrado when listing page is loaded', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice']);

    Carbon::setTestNow('2026-04-21 09:00:00');

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-04-20',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->get(route('procesos.index'))
        ->assertOk();

    $this->assertDatabaseHas('tbl_proceso', [
        'id_pro' => $proceso->id_pro,
        'estado_pro' => 'CERRADO',
    ]);

});

test('expired proceso cannot be reenabled manually', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_estado']);

    Carbon::setTestNow('2026-04-21 09:00:00');

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-04-20',
        'estado_pro' => 'DESHABILITADO',
    ]);

    $this->actingAs($usuario)
        ->patch(route('procesos.estado', $proceso))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'error', 'message' => 'El proceso ya venció por fecha y ahora se encuentra cerrado.']);

    $this->assertDatabaseHas('tbl_proceso', [
        'id_pro' => $proceso->id_pro,
        'estado_pro' => 'CERRADO',
    ]);

});

test('authorized users can delete a proceso without configuraciones relacionadas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_eliminar']);

    $proceso = Proceso::query()->create([
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->delete(route('procesos.destroy', $proceso))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Proceso eliminado correctamente.']);

    $procesoEliminado = Proceso::withTrashed()->find($proceso->id_pro);

    expect($procesoEliminado)->not->toBeNull();
    expect($procesoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('proceso requires the AAAA-N name format and a valid date range', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_crear']);

    $this->actingAs($usuario)
        ->post(route('procesos.store'), ['nombre' => 'Proceso 2026-I', 'fecha_inicio' => '2026-07-31', 'fecha_fin' => '2026-04-01', 'anio_egreso_max_ordinario' => '26'])
        ->assertSessionHasErrors([
            'nombre' => 'El nombre debe tener el formato AAAA-N, por ejemplo 2026-2.',
            'fecha_fin' => 'El cierre debe ser posterior a la apertura.',
            'anio_egreso_max_ordinario' => 'Ingrese un año de cuatro dígitos.',
        ]);
});

test('proceso name must be unique including deleted procesos', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_crear']);
    Proceso::query()->create([
        'codigo_pro' => '2026_2',
        'nombre_pro' => '2026-2',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ])->delete();

    $this->actingAs($usuario)
        ->post(route('procesos.store'), ['nombre' => '2026-2', 'fecha_inicio' => '2026-04-10', 'fecha_fin' => '2026-07-15'])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un proceso con ese nombre.']);
});
