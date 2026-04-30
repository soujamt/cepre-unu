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

function otorgarPermisosAulaProceso(Usuario $usuario, array $claves): void
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

        $accion = Accion::query()->firstOrCreate(
            ['clave_accion' => $accionClave],
            [
                'nombre_accion' => Str::upper($accionClave),
                'estado_accion' => 'HABILITADO',
            ],
        );

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
 * @return array{semestre: Semestre, proceso: Proceso, aula: Aula, turno: Turno, asignacion: ProcesoAula}
 */
function crearAulaProcesoContexto(int $indice, string $estado = 'HABILITADO'): array
{
    $semestre = Semestre::query()->create([
        'codigo_sem' => "semestre_2026_{$indice}",
        'nombre_sem' => "SEMESTRE 2026-{$indice}",
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => "proceso_ordinario_2026_{$indice}",
        'nombre_pro' => "PROCESO ORDINARIO 2026-{$indice}",
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);

    $aula = Aula::query()->create([
        'nombre_aul' => "AULA {$indice}",
        'piso_aul' => 'PISO 1',
        'lugar_aul' => $indice === 1 ? 'UNU' : 'UAP',
        'orden_aul' => $indice,
        'estado_aul' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "turno_{$indice}",
        'nombre_tur' => $indice === 1 ? 'MANANA' : 'TARDE',
        'estado_tur' => 'HABILITADO',
    ]);

    $asignacion = ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => $estado,
    ]);

    return compact('semestre', 'proceso', 'aula', 'turno', 'asignacion');
}

test('authorized users can access aulas por proceso page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice']);

    $this->actingAs($usuario)
        ->get(route('aulas.index'))
        ->assertOk()
        ->assertSee('AULAS POR PROCESO');
});

test('users can filter aulas por proceso by semestre proceso turno and estado', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice']);

    $contextoA = crearAulaProcesoContexto(1, 'HABILITADO');
    $contextoB = crearAulaProcesoContexto(2, 'DESHABILITADO');

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.aulas-proceso')
        ->assertSee($contextoA['aula']->nombre_aul)
        ->assertSee($contextoB['aula']->nombre_aul)
        ->set('idSemestre', (string) $contextoA['semestre']->id_sem)
        ->assertSee($contextoA['aula']->nombre_aul)
        ->assertDontSee($contextoB['aula']->nombre_aul)
        ->set('idSemestre', '')
        ->set('idProceso', (string) $contextoB['proceso']->id_pro)
        ->assertDontSee($contextoA['aula']->nombre_aul)
        ->assertSee($contextoB['aula']->nombre_aul)
        ->set('idProceso', '')
        ->set('idTurno', (string) $contextoB['turno']->id_tur)
        ->assertDontSee($contextoA['aula']->nombre_aul)
        ->assertSee($contextoB['aula']->nombre_aul)
        ->set('idTurno', '')
        ->set('filtroEstado.DESHABILITADO', true)
        ->assertDontSee($contextoA['aula']->nombre_aul)
        ->assertSee($contextoB['aula']->nombre_aul);
});

test('asignar aulas filters procesos by semestre', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contextoA = crearAulaProcesoContexto(1);
    $contextoB = crearAulaProcesoContexto(2);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.aulas-proceso-asignar')
        ->assertSee($contextoA['proceso']->nombre_pro)
        ->assertSee($contextoB['proceso']->nombre_pro)
        ->set('idSemestre', (string) $contextoA['semestre']->id_sem)
        ->assertSee($contextoA['proceso']->nombre_pro)
        ->assertDontSee($contextoB['proceso']->nombre_pro);
});

test('asignar aulas allows the same aula in different turnos for one proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contexto = crearAulaProcesoContexto(1);

    $turnoTarde = Turno::query()->create([
        'codigo_tur' => 'turno_tarde',
        'nombre_tur' => 'TARDE',
        'estado_tur' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.aulas-proceso-asignar')
        ->set('idSemestre', (string) $contexto['semestre']->id_sem)
        ->set('idProceso', (string) $contexto['proceso']->id_pro)
        ->set('idTurno', (string) $turnoTarde->id_tur)
        ->set("seleccionadas.{$contexto['aula']->id_aul}", true)
        ->set("capacidades.{$contexto['aula']->id_aul}", '50')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Aulas asignadas correctamente.', type: 'success');

    expect(ProcesoAula::query()
        ->where('id_proceso', $contexto['proceso']->id_pro)
        ->where('id_aula', $contexto['aula']->id_aul)
        ->count())->toBe(2);

    expect(ProcesoAula::query()
        ->where('id_proceso', $contexto['proceso']->id_pro)
        ->where('id_turno', $turnoTarde->id_tur)
        ->where('id_aula', $contexto['aula']->id_aul)
        ->value('capacidad_pau'))->toBe(50);
});

test('asignar aulas restores a soft deleted assignment for the same proceso turno and aula', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_crear']);

    $contexto = crearAulaProcesoContexto(1);
    $contexto['asignacion']->delete();

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.aulas-proceso-asignar')
        ->set('idSemestre', (string) $contexto['semestre']->id_sem)
        ->set('idProceso', (string) $contexto['proceso']->id_pro)
        ->set('idTurno', (string) $contexto['turno']->id_tur)
        ->set("seleccionadas.{$contexto['aula']->id_aul}", true)
        ->set("capacidades.{$contexto['aula']->id_aul}", '55')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Aulas asignadas correctamente.', type: 'success');

    $asignacionRestaurada = ProcesoAula::query()
        ->withTrashed()
        ->findOrFail($contexto['asignacion']->id_pau);

    expect($asignacionRestaurada->trashed())->toBeFalse()
        ->and($asignacionRestaurada->capacidad_pau)->toBe(55)
        ->and($asignacionRestaurada->estado_pau->value)->toBe('HABILITADO');
});
