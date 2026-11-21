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
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

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
    crearTurnoPrueba()->update(['hora_inicio_tur' => '07:00', 'hora_fin_tur' => '12:00']);

    $this->actingAs($usuario)
        ->get(route('turnos.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mantenedores/turnos')
            ->has('turnos.data', 1)
            ->where('turnos.data.0.codigo', 'MAN')
            ->where('turnos.data.0.descripcion', 'MAÑANA (07:00 am - 12:00 pm)')
            ->where('turnos.data.0.horaInicio', '07:00')
            ->where('turnos.data.0.horaFin', '12:00'));
});

test('turnos can be searched and filtered by state', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice']);
    crearTurnoPrueba('MAN', 'MAÑANA');
    crearTurnoPrueba('TAR', 'TARDE')->update(['estado_tur' => 'DESHABILITADO']);

    $this->actingAs($usuario)
        ->get(route('turnos.index', ['buscar' => 'tar']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('turnos.data', 1)->where('turnos.data.0.codigo', 'TAR'));

    $this->actingAs($usuario)
        ->get(route('turnos.index', ['estado' => ['HABILITADO']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('turnos.data', 1)->where('turnos.data.0.codigo', 'MAN'));
});

test('authenticated users without permiso cannot access turnos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('turnos.index'))
        ->assertForbidden();
});

test('users without crear permiso cannot register a turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice']);

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'INT', 'nombre' => 'Intermedio'])
        ->assertForbidden();

    expect(Turno::query()->count())->toBe(0);
});

test('authorized users can register a turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_crear']);

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'int', 'nombre' => 'Intermedio', 'hora_inicio' => '', 'hora_fin' => ''])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Turno registrado correctamente.']);

    $this->assertDatabaseHas('tbl_turno', [
        'codigo_tur' => 'INT',
        'nombre_tur' => 'INTERMEDIO',
        'hora_inicio_tur' => null,
        'hora_fin_tur' => null,
        'estado_tur' => 'HABILITADO',
    ]);
});

test('a turno keeps its schedule and shows it with the name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_crear']);

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'VIR', 'nombre' => 'noche virtual', 'hora_inicio' => '17:00', 'hora_fin' => '16:00'])
        ->assertSessionHasErrors(['hora_fin' => 'La hora de fin debe ser posterior a la de inicio.']);

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'VIR', 'nombre' => 'noche virtual', 'hora_inicio' => '17:00', 'hora_fin' => ''])
        ->assertSessionHasErrors(['hora_fin' => 'Indique la hora de fin.']);

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'VIR', 'nombre' => 'noche virtual', 'hora_inicio' => '17:00', 'hora_fin' => '22:00'])
        ->assertSessionHasNoErrors();

    $turno = Turno::query()->where('codigo_tur', 'VIR')->sole();

    expect($turno->descripcionConHorario())->toBe('NOCHE VIRTUAL (05:00 pm - 10:00 pm)');
});

test('turno code and name must be unique including deleted turnos', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_crear']);
    crearTurnoPrueba('MAN', 'MAÑANA');
    crearTurnoPrueba('TAR', 'TARDE')->delete();

    $this->actingAs($usuario)
        ->post(route('turnos.store'), ['codigo' => 'man', 'nombre' => 'tarde'])
        ->assertSessionHasErrors([
            'codigo' => 'Ya existe un turno con ese código.',
            'nombre' => 'Ya existe un turno con ese nombre.',
        ]);
});

test('authorized users can update and change state of a turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_editar', 'turno_estado']);
    $turno = crearTurnoPrueba();

    $this->actingAs($usuario)
        ->put(route('turnos.update', $turno), ['codigo' => 'MAN', 'nombre' => 'Mañana temprano'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.message', 'Turno actualizado correctamente.');

    $this->actingAs($usuario)
        ->patch(route('turnos.estado', $turno))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Estado del turno actualizado.']);

    $turno->refresh();

    expect($turno->nombre_tur)->toBe('MAÑANA TEMPRANO')
        ->and($turno->estado_tur->value)->toBe('DESHABILITADO');
});

test('authorized users can delete an unused turno', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTurno($usuario, ['turno_indice', 'turno_eliminar']);
    $turno = crearTurnoPrueba();

    $this->actingAs($usuario)
        ->delete(route('turnos.destroy', $turno))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Turno eliminado correctamente.']);

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

    $this->actingAs($usuario)
        ->delete(route('turnos.destroy', $turno))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', [
            'type' => 'error',
            'message' => 'El turno se usa en aulas por proceso y no puede eliminarse. Puede deshabilitarlo.',
        ]);

    $this->assertNotSoftDeleted('tbl_turno', ['id_tur' => $turno->id_tur], deletedAtColumn: 'eliminado_en');
});
