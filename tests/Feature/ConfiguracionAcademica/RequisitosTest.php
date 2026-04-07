<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Proceso;
use App\Models\RequisitoExpediente;
use App\Models\Rol;
use App\Models\Semestre;
use App\Models\TipoExpediente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosRequisito(Usuario $usuario, array $claves): void
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

function crearProcesoRequisito(int $indice): Proceso
{
    $semestre = Semestre::query()->create([
        'codigo_sem' => "semestre_2026_{$indice}",
        'nombre_sem' => "SEMESTRE 2026-{$indice}",
        'fecha_inicio_inscripcion_sem' => '2026-01-05',
        'fecha_fin_inscripcion_sem' => '2026-03-31',
        'estado_sem' => 'HABILITADO',
    ]);

    return Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => "proceso_{$indice}",
        'nombre_pro' => "PROCESO {$indice}",
        'fecha_inicio_pro' => '2026-01-10',
        'fecha_fin_pro' => '2026-03-15',
        'estado_pro' => 'HABILITADO',
    ]);
}

function crearTipoExpedienteRequisito(int $indice): TipoExpediente
{
    return TipoExpediente::query()->create([
        'codigo_tex' => "TEX{$indice}",
        'nombre_tex' => "TIPO {$indice}",
        'descripcion_tex' => 'Tipo de expediente de prueba',
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
    ]);
}

test('authorized users can access requisitos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice']);

    $this->actingAs($usuario)
        ->get(route('requisitos.index'))
        ->assertOk()
        ->assertSee('REQUISITOS');
});

test('authenticated users without permiso cannot access requisitos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('requisitos.index'))
        ->assertForbidden();
});

test('authorized users can register a requisito', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice', 'requisito_expediente_crear']);

    $proceso = crearProcesoRequisito(1);
    $tipoExpediente = crearTipoExpedienteRequisito(1);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.requisitos')
        ->set('requisitoForm.id_proceso', (string) $proceso->id_pro)
        ->set('requisitoForm.id_tipo_expediente', (string) $tipoExpediente->id_tex)
        ->set('requisitoForm.obligatorio', true)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_requisito_expediente', [
        'id_proceso' => $proceso->id_pro,
        'id_tipo_expediente' => $tipoExpediente->id_tex,
        'obligatorio_rex' => true,
        'estado_rex' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update a requisito', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice', 'requisito_expediente_editar']);

    $procesoA = crearProcesoRequisito(1);
    $procesoB = crearProcesoRequisito(2);
    $tipoExpedienteA = crearTipoExpedienteRequisito(1);
    $tipoExpedienteB = crearTipoExpedienteRequisito(2);

    $requisito = RequisitoExpediente::query()->create([
        'id_proceso' => $procesoA->id_pro,
        'id_tipo_expediente' => $tipoExpedienteA->id_tex,
        'obligatorio_rex' => true,
        'estado_rex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.requisitos')
        ->call('cargarRequisito', $requisito->id_rex)
        ->set('requisitoForm.id_proceso', (string) $procesoB->id_pro)
        ->set('requisitoForm.id_tipo_expediente', (string) $tipoExpedienteB->id_tex)
        ->set('requisitoForm.obligatorio', false)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_requisito_expediente', [
        'id_rex' => $requisito->id_rex,
        'id_proceso' => $procesoB->id_pro,
        'id_tipo_expediente' => $tipoExpedienteB->id_tex,
        'obligatorio_rex' => false,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a requisito', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice', 'requisito_expediente_estado']);

    $proceso = crearProcesoRequisito(1);
    $tipoExpediente = crearTipoExpedienteRequisito(1);

    $requisito = RequisitoExpediente::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_tipo_expediente' => $tipoExpediente->id_tex,
        'obligatorio_rex' => true,
        'estado_rex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.requisitos')
        ->call('cambiarEstado', $requisito->id_rex)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_requisito_expediente', [
        'id_rex' => $requisito->id_rex,
        'estado_rex' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a requisito', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice', 'requisito_expediente_eliminar']);

    $proceso = crearProcesoRequisito(1);
    $tipoExpediente = crearTipoExpedienteRequisito(1);

    $requisito = RequisitoExpediente::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_tipo_expediente' => $tipoExpediente->id_tex,
        'obligatorio_rex' => true,
        'estado_rex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.requisitos')
        ->call('eliminar', $requisito->id_rex)
        ->assertHasNoErrors();

    $requisitoEliminado = RequisitoExpediente::withTrashed()->find($requisito->id_rex);

    expect($requisitoEliminado)->not->toBeNull();
    expect($requisitoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('requisito validates duplicate process and tipo expediente combination', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosRequisito($usuario, ['requisito_expediente_indice', 'requisito_expediente_crear']);

    $proceso = crearProcesoRequisito(1);
    $tipoExpediente = crearTipoExpedienteRequisito(1);

    RequisitoExpediente::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_tipo_expediente' => $tipoExpediente->id_tex,
        'obligatorio_rex' => true,
        'estado_rex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.requisitos')
        ->set('requisitoForm.id_proceso', (string) $proceso->id_pro)
        ->set('requisitoForm.id_tipo_expediente', (string) $tipoExpediente->id_tex)
        ->set('requisitoForm.obligatorio', true)
        ->call('guardar')
        ->assertHasErrors(['requisitoForm.id_proceso']);
});
