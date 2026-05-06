<?php

use App\Models\Accion;
use App\Models\Aula;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\Turno;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosAula(Usuario $usuario, array $claves): void
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

test('authorized users can access aulas page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice']);

    $this->actingAs($usuario)
        ->get(route('mantenedor-aulas.index'))
        ->assertOk()
        ->assertSee('AULAS');
});

test('authenticated users without permiso cannot access aulas page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('mantenedor-aulas.index'))
        ->assertForbidden();
});

test('authorized users can register an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->set('aulaForm.nombre', 'Aula 101')
        ->set('aulaForm.piso', 'PISO 1')
        ->set('aulaForm.lugar', 'UNU')
        ->set('aulaForm.orden', '1')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_aula', [
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_editar']);

    $aula = Aula::query()->create([
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->call('cargarAula', $aula->id_aul)
        ->set('aulaForm.nombre', 'Aula 201')
        ->set('aulaForm.piso', 'PISO 2')
        ->set('aulaForm.lugar', 'UAP')
        ->set('aulaForm.orden', '2')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_aula', [
        'id_aul' => $aula->id_aul,
        'nombre_aul' => 'AULA 201',
        'piso_aul' => 'PISO 2',
        'lugar_aul' => 'UAP',
        'orden_aul' => 2,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of an aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_estado']);

    $aula = Aula::query()->create([
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->call('cambiarEstado', $aula->id_aul)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_aula', [
        'id_aul' => $aula->id_aul,
        'estado_aul' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete an aula without assignments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_eliminar']);

    $aula = Aula::query()->create([
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->call('eliminar', $aula->id_aul)
        ->assertHasNoErrors();

    $aulaEliminada = Aula::query()->withTrashed()->find($aula->id_aul);

    expect($aulaEliminada)->not->toBeNull();
    expect($aulaEliminada?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('authenticated users cannot register duplicated aulas by name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_crear']);

    Aula::query()->create([
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->set('aulaForm.nombre', 'aula 101')
        ->set('aulaForm.piso', 'PISO 2')
        ->set('aulaForm.lugar', 'UAP')
        ->set('aulaForm.orden', '2')
        ->call('guardar')
        ->assertHasErrors(['aulaForm.nombre']);
});

test('authenticated users cannot delete an aula with proceso assignments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAula($usuario, ['aula_indice', 'aula_eliminar']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_1',
        'nombre_sem' => 'SEMESTRE 2026-1',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_1',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => 'turno_1',
        'nombre_tur' => 'MANANA',
        'estado_tur' => 'HABILITADO',
    ]);

    $aula = Aula::query()->create([
        'nombre_aul' => 'AULA 101',
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => 1,
        'estado_aul' => 'HABILITADO',
    ]);

    ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.aulas')
        ->call('eliminar', $aula->id_aul)
        ->assertDispatched('toast', message: 'El aula tiene asignaciones por proceso y no puede eliminarse.', type: 'error');

    expect(Aula::query()->find($aula->id_aul))->not->toBeNull();
});
