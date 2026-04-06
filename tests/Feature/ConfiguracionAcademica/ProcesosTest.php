<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
        ->assertSee('PROCESOS');
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

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->set('procesoForm.id_semestre', (string) $semestre->id_sem)
        ->set('procesoForm.nombre', 'Proceso ordinario 2026-I')
        ->set('procesoForm.fecha_inicio', '2026-01-10')
        ->set('procesoForm.fecha_fin', '2026-03-15')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_proceso', [
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'estado_pro' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_editar']);

    $semestreA = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $semestreB = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_ii',
        'nombre_sem' => 'SEMESTRE 2026-II',
        'fecha_inicio_inscripcion_sem' => '2026-04-01',
        'fecha_fin_inscripcion_sem' => '2026-07-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestreA->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('cargarProceso', $proceso->id_pro)
        ->set('procesoForm.id_semestre', (string) $semestreB->id_sem)
        ->set('procesoForm.nombre', 'Proceso extraordinario 2026-II')
        ->set('procesoForm.fecha_inicio', '2026-04-10')
        ->set('procesoForm.fecha_fin', '2026-07-15')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_proceso', [
        'id_pro' => $proceso->id_pro,
        'id_semestre' => $semestreB->id_sem,
        'codigo_pro' => 'proceso_extraordinario_2026_ii',
        'nombre_pro' => 'PROCESO EXTRAORDINARIO 2026-II',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_estado']);

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

test('authorized users can delete a proceso without configuraciones relacionadas', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_eliminar']);

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

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->call('eliminar', $proceso->id_pro)
        ->assertHasNoErrors();

    $procesoEliminado = Proceso::withTrashed()->find($proceso->id_pro);

    expect($procesoEliminado)->not->toBeNull();
    expect($procesoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('proceso requires semestre and valid date range', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosProceso($usuario, ['proceso_indice', 'proceso_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.procesos')
        ->set('procesoForm.id_semestre', '')
        ->set('procesoForm.nombre', 'Proceso 2026-I')
        ->set('procesoForm.fecha_inicio', '2026-07-31')
        ->set('procesoForm.fecha_fin', '2026-04-01')
        ->call('guardar')
        ->assertHasErrors([
            'procesoForm.id_semestre',
            'procesoForm.fecha_fin',
        ]);
});
