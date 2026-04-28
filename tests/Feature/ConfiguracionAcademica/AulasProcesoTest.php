<?php

use App\Models\Accion;
use App\Models\Aula;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Rol;
use App\Models\Semestre;
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
 * @return array{semestre: Semestre, proceso: Proceso, aula: Aula, asignacion: ProcesoAula}
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

    $asignacion = ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => $estado,
    ]);

    return compact('semestre', 'proceso', 'aula', 'asignacion');
}

test('authorized users can access aulas por proceso page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosAulaProceso($usuario, ['aula_proceso_indice']);

    $this->actingAs($usuario)
        ->get(route('aulas.index'))
        ->assertOk()
        ->assertSee('AULAS POR PROCESO');
});

test('users can filter aulas por proceso by semestre proceso and estado', function () {
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
