<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\TipoExpediente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosTipoExpediente(Usuario $usuario, array $claves): void
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

test('authorized users can access tipos de expediente page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice']);

    $this->actingAs($usuario)
        ->get(route('tipos-expediente.index'))
        ->assertOk()
        ->assertSee('TIPOS DE EXPEDIENTE');
});

test('authenticated users without permiso cannot access tipos de expediente page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('tipos-expediente.index'))
        ->assertForbidden();
});

test('authorized users can register a tipo de expediente', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice', 'tipo_expediente_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.tipos-expediente')
        ->set('tipoExpedienteForm.codigo', 'fot')
        ->set('tipoExpedienteForm.nombre', 'Fotografia')
        ->set('tipoExpedienteForm.descripcion', 'Foto del postulante')
        ->set('tipoExpedienteForm.obligatorio', true)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_tipo_expediente', [
        'codigo_tex' => 'FOT',
        'nombre_tex' => 'FOTOGRAFIA',
        'descripcion_tex' => 'Foto del postulante',
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can update a tipo de expediente', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice', 'tipo_expediente_editar']);

    $tipoExpediente = TipoExpediente::query()->create([
        'codigo_tex' => 'FOT',
        'nombre_tex' => 'FOTOGRAFIA',
        'descripcion_tex' => 'Foto simple',
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.tipos-expediente')
        ->call('cargarTipoExpediente', $tipoExpediente->id_tex)
        ->set('tipoExpedienteForm.codigo', 'FOTO')
        ->set('tipoExpedienteForm.nombre', 'Fotografia actualizada')
        ->set('tipoExpedienteForm.descripcion', 'Foto actualizada')
        ->set('tipoExpedienteForm.obligatorio', false)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_tipo_expediente', [
        'id_tex' => $tipoExpediente->id_tex,
        'codigo_tex' => 'FOTO',
        'nombre_tex' => 'FOTOGRAFIA ACTUALIZADA',
        'descripcion_tex' => 'Foto actualizada',
        'obligatorio_tex' => false,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a tipo de expediente', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice', 'tipo_expediente_estado']);

    $tipoExpediente = TipoExpediente::query()->create([
        'codigo_tex' => 'FOT',
        'nombre_tex' => 'FOTOGRAFIA',
        'descripcion_tex' => null,
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.tipos-expediente')
        ->call('cambiarEstado', $tipoExpediente->id_tex)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_tipo_expediente', [
        'id_tex' => $tipoExpediente->id_tex,
        'estado_tex' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a tipo de expediente', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice', 'tipo_expediente_eliminar']);

    $tipoExpediente = TipoExpediente::query()->create([
        'codigo_tex' => 'FOT',
        'nombre_tex' => 'FOTOGRAFIA',
        'descripcion_tex' => null,
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.tipos-expediente')
        ->call('eliminar', $tipoExpediente->id_tex)
        ->assertHasNoErrors();

    $tipoExpedienteEliminado = TipoExpediente::withTrashed()->find($tipoExpediente->id_tex);

    expect($tipoExpedienteEliminado)->not->toBeNull();
    expect($tipoExpedienteEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('authenticated users cannot register duplicated tipo de expediente by code or name', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosTipoExpediente($usuario, ['tipo_expediente_indice', 'tipo_expediente_crear']);

    TipoExpediente::query()->create([
        'codigo_tex' => 'FOT',
        'nombre_tex' => 'FOTOGRAFIA',
        'descripcion_tex' => null,
        'obligatorio_tex' => true,
        'estado_tex' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::mantenedores.tipos-expediente')
        ->set('tipoExpedienteForm.codigo', 'fot')
        ->set('tipoExpedienteForm.nombre', 'Fotografia')
        ->call('guardar')
        ->assertHasErrors([
            'tipoExpedienteForm.codigo',
            'tipoExpedienteForm.nombre',
        ]);
});
