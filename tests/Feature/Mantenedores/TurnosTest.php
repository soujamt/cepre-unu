<?php

use App\Models\Accion;
use App\Models\Aula;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Rol;
use App\Models\Turno;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosTurno(Usuario $usuario, array $claves): void
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

function crearTurnoPrueba(string $codigo = 'MAN', string $nombre = 'MAÑANA'): Turno
{
    return Turno::query()->create([
        'codigo_tur' => $codigo,
        'nombre_tur' => $nombre,
        'estado_tur' => 'HABILITADO',
    ]);
}

test('authorized users can access turnos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice']);

    $this->actingAs($usuario)
        ->get(route('turnos.index'))
        ->assertOk()
        ->assertSee('Turnos');
});

test('authenticated users without permiso cannot access turnos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('turnos.index'))
        ->assertForbidden();
});

test('authorized users can register a turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.turnos')
        ->call('cargarTurno')
        ->set('turnoForm.codigo', 'int')
        ->set('turnoForm.nombre', 'Intermedio')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Turno registrado correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_turno', [
        'codigo_tur' => 'INT',
        'nombre_tur' => 'INTERMEDIO',
        'estado_tur' => 'HABILITADO',
    ]);
});

test('turno code and name must be unique', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_crear']);
    crearTurnoPrueba('MAN', 'MAÑANA');

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.turnos')
        ->set('turnoForm.codigo', 'man')
        ->set('turnoForm.nombre', 'mañana')
        ->call('guardar')
        ->assertHasErrors(['turnoForm.codigo', 'turnoForm.nombre']);
});

test('authorized users can update and change state of a turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_editar', 'turno_estado']);
    $turno = crearTurnoPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.turnos')
        ->call('cargarTurno', $turno->id_tur)
        ->set('turnoForm.nombre', 'Mañana temprano')
        ->call('guardar')
        ->assertHasNoErrors()
        ->call('cambiarEstado', $turno->id_tur)
        ->assertDispatched('toast', message: 'Estado del turno actualizado.', type: 'success');

    $turno->refresh();

    expect($turno->nombre_tur)->toBe('MAÑANA TEMPRANO')
        ->and($turno->estado_tur->value)->toBe('DESHABILITADO');
});

test('authorized users can delete an unused turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_eliminar']);
    $turno = crearTurnoPrueba();

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.turnos')
        ->call('eliminar', $turno->id_tur)
        ->assertDispatched('toast', message: 'Turno eliminado correctamente.', type: 'success');

    $this->assertSoftDeleted('tbl_turno', ['id_tur' => $turno->id_tur], deletedAtColumn: 'eliminado_en');
});

test('turno used in aulas por proceso cannot be deleted', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_eliminar']);
    $turno = crearTurnoPrueba();
    $proceso = Proceso::query()->create([
        'codigo_pro' => '2026_1',
        'nombre_pro' => '2026-1',
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
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

    Livewire::test('pages::mantenedores.turnos')
        ->call('eliminar', $turno->id_tur)
        ->assertDispatched('toast', message: 'El turno se usa en aulas por proceso y no puede eliminarse. Puede deshabilitarlo.', type: 'error');

    $this->assertNotSoftDeleted('tbl_turno', ['id_tur' => $turno->id_tur], deletedAtColumn: 'eliminado_en');
});
