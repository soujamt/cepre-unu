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
use Livewire\Livewire;

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
        ->assertSee('Procesos');
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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->set('procesoForm.nombre', '2026-2')
        ->set('procesoForm.fecha_inicio', '2026-01-10T08:00')
        ->set('procesoForm.fecha_fin', '2026-03-15T18:30')
        ->call('guardar')
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('cargarProceso', $proceso->id_pro)
        ->set('procesoForm.nombre', '2026-3')
        ->set('procesoForm.fecha_inicio', '2026-04-10')
        ->set('procesoForm.fecha_fin', '2026-07-15')
        ->call('guardar')
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('cambiarEstado', $proceso->id_pro)
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->assertSee($procesoCerrado->nombre_pro);
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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->assertHasNoErrors();

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('cambiarEstado', $proceso->id_pro)
        ->assertDispatched('toast', message: 'El proceso ya venció por fecha y ahora se encuentra cerrado.', type: 'error');

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('eliminar', $proceso->id_pro)
        ->assertHasNoErrors();

    $procesoEliminado = Proceso::withTrashed()->find($proceso->id_pro);

    expect($procesoEliminado)->not->toBeNull();
    expect($procesoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('proceso requires the AAAA-N name format and a valid date range', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->set('procesoForm.nombre', 'Proceso 2026-I')
        ->set('procesoForm.fecha_inicio', '2026-07-31')
        ->set('procesoForm.fecha_fin', '2026-04-01')
        ->call('guardar')
        ->assertHasErrors([
            'procesoForm.nombre' => 'regex',
            'procesoForm.fecha_fin',
        ]);
});
