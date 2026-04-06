<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosSemestre(Usuario $usuario, array $claves): void
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

test('authorized users can access semestres page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice']);

    $this->actingAs($usuario)
        ->get(route('semestres.index'))
        ->assertOk()
        ->assertSee('SEMESTRES');
});

test('authenticated users without permiso cannot access semestres page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('semestres.index'))
        ->assertForbidden();
});

test('authorized users can register a semestre', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->set('semestreForm.nombre', 'Semestre 2026-I')
        ->set('semestreForm.fecha_inicio_inscripcion', '2026-01-05')
        ->set('semestreForm.fecha_fin_inscripcion', '2026-03-31')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_semestre', [
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'estado_sem' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $semestre = Semestre::query()->where('codigo_sem', 'semestre_2026_i')->firstOrFail();

    expect($semestre->fecha_inicio_inscripcion_sem?->format('Y-m-d'))->toBe('2026-01-05');
    expect($semestre->fecha_fin_inscripcion_sem?->format('Y-m-d'))->toBe('2026-03-31');
});

test('authorized users can update a semestre', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_editar']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => '2026-I',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->call('cargarSemestre', $semestre->id_sem)
        ->set('semestreForm.nombre', 'Semestre 2026-II')
        ->set('semestreForm.fecha_inicio_inscripcion', '2026-04-01')
        ->set('semestreForm.fecha_fin_inscripcion', '2026-07-31')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_semestre', [
        'id_sem' => $semestre->id_sem,
        'codigo_sem' => 'semestre_2026_ii',
        'nombre_sem' => 'SEMESTRE 2026-II',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    expect($semestre->fresh()?->fecha_inicio_inscripcion_sem?->format('Y-m-d'))->toBe('2026-04-01');
    expect($semestre->fresh()?->fecha_fin_inscripcion_sem?->format('Y-m-d'))->toBe('2026-07-31');
});

test('authorized users can change state of a semestre', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_estado']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => '2026-I',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->call('cambiarEstado', $semestre->id_sem)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_semestre', [
        'id_sem' => $semestre->id_sem,
        'estado_sem' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a semestre without procesos', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_eliminar']);

    $semestre = Semestre::query()->create([
        'codigo_sem' => '2026-I',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->call('eliminar', $semestre->id_sem)
        ->assertHasNoErrors();

    $semestreEliminado = Semestre::withTrashed()->find($semestre->id_sem);

    expect($semestreEliminado)->not->toBeNull();
    expect($semestreEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('authenticated users cannot register duplicated semestre by code or name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_crear']);

    Semestre::query()->create([
        'codigo_sem' => '2026-I',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->set('semestreForm.nombre', 'Semestre 2026-I')
        ->set('semestreForm.fecha_inicio_inscripcion', '2026-04-01')
        ->set('semestreForm.fecha_fin_inscripcion', '2026-07-31')
        ->call('guardar')
        ->assertHasErrors([
            'semestreForm.nombre',
        ]);
});

test('semestre requires end date greater than or equal to start date', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosSemestre($usuario, ['semestre_indice', 'semestre_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.semestres')
        ->set('semestreForm.nombre', 'Semestre 2026-I')
        ->set('semestreForm.fecha_inicio_inscripcion', '2026-07-31')
        ->set('semestreForm.fecha_fin_inscripcion', '2026-04-01')
        ->call('guardar')
        ->assertHasErrors(['semestreForm.fecha_fin_inscripcion']);
});
