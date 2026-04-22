<?php

use App\Models\Accion;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
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

function otorgarPermisosConvenio(Usuario $usuario, array $claves): void
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

function crearProcesoConvenio(int $indice): Proceso
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

test('authorized users can access convenios page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice']);

    $this->actingAs($usuario)
        ->get(route('convenios.index'))
        ->assertOk()
        ->assertSee('CONVENIOS');
});

test('authenticated users without permiso cannot access convenios page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('convenios.index'))
        ->assertForbidden();
});

test('authorized users can register a convenio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_crear']);

    $proceso = crearProcesoConvenio(1);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->set('convenioForm.codigo', 'HIJO_UNU')
        ->set('convenioForm.nombre', 'Hijo trabajador UNU')
        ->set('convenioForm.descripcion', 'Beneficio por hijo de trabajador')
        ->set('convenioForm.tipo_beneficio', 'DESCUENTO')
        ->set('convenioForm.monto_descuento', '300.00')
        ->set('convenioForm.id_proceso', (string) $proceso->id_pro)
        ->set('convenioForm.fecha_inicio', '2026-01-10')
        ->set('convenioForm.fecha_fin', '2026-03-15')
        ->set('convenioForm.cupo_maximo', '100')
        ->call('guardar')
        ->assertHasNoErrors();

    $convenio = Convenio::query()->where('codigo_con', 'HIJO_UNU')->first();

    expect($convenio)->not->toBeNull();

    $this->assertDatabaseHas('tbl_convenio', [
        'codigo_con' => 'HIJO_UNU',
        'nombre_con' => 'HIJO TRABAJADOR UNU',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 300.00,
        'estado_con' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $this->assertDatabaseHas('tbl_convenio_proceso', [
        'id_convenio' => $convenio?->id_con,
        'id_proceso' => $proceso->id_pro,
        'cupo_maximo_cpr' => 100,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
        'id_usuario_creador' => $usuario->id_usu,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('new enabled procesos are available in convenio form even without convenios registrados', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_crear']);

    $proceso = crearProcesoConvenio(9);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->call('cargarConvenio')
        ->assertSee($proceso->nombre_pro);
});

test('historical convenio filters include closed semestre and its proceso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice']);

    $proceso = crearProcesoConvenio(10);
    $proceso->semestre->update(['estado_sem' => 'CERRADO']);
    $proceso->update(['estado_pro' => 'CERRADO']);

    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_HIST',
        'nombre_con' => 'CONVENIO HISTORICO',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-01-10',
        'fecha_fin_cpr' => '2026-03-15',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->assertSee($proceso->semestre->nombre_sem)
        ->assertSee($proceso->nombre_pro)
        ->set('idSemestre', (string) $proceso->id_semestre)
        ->assertSee($proceso->nombre_pro);
});

test('convenio filters include current enabled semestre even without convenios and historical semestre with records', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice']);

    $procesoHistorico = crearProcesoConvenio(11);
    $procesoHistorico->semestre->update(['estado_sem' => 'CERRADO']);
    $procesoHistorico->update(['estado_pro' => 'CERRADO']);

    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_MIX',
        'nombre_con' => 'CONVENIO MIXTO',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $procesoHistorico->id_pro,
        'fecha_inicio_cpr' => '2026-01-10',
        'fecha_fin_cpr' => '2026-03-15',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $procesoActual = crearProcesoConvenio(12);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->assertSee($procesoHistorico->semestre->nombre_sem)
        ->assertSee($procesoActual->semestre->nombre_sem)
        ->set('idSemestre', (string) $procesoActual->id_semestre)
        ->assertSee($procesoActual->nombre_pro);
});

test('authorized users can update a convenio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_editar']);

    $procesoA = crearProcesoConvenio(1);
    $procesoB = crearProcesoConvenio(2);

    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_A',
        'nombre_con' => 'CONVENIO A',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    $convenioProceso = ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $procesoA->id_pro,
        'fecha_inicio_cpr' => '2026-01-10',
        'fecha_fin_cpr' => '2026-03-15',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->call('cargarConvenio', $convenioProceso->id_cpr)
        ->set('convenioForm.codigo', 'CONV_B')
        ->set('convenioForm.nombre', 'Convenio B')
        ->set('convenioForm.descripcion', 'Nueva descripcion')
        ->set('convenioForm.tipo_beneficio', 'EXONERACION_PARCIAL')
        ->set('convenioForm.porcentaje_descuento', '50')
        ->set('convenioForm.monto_descuento', '')
        ->set('convenioForm.id_proceso', (string) $procesoB->id_pro)
        ->set('convenioForm.fecha_inicio', '2026-02-01')
        ->set('convenioForm.fecha_fin', '2026-03-15')
        ->set('convenioForm.cupo_maximo', '80')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_convenio', [
        'id_con' => $convenio->id_con,
        'codigo_con' => 'CONV_B',
        'nombre_con' => 'CONVENIO B',
        'tipo_beneficio_con' => 'EXONERACION_PARCIAL',
        'porcentaje_descuento_con' => 50.00,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);

    $this->assertDatabaseHas('tbl_convenio_proceso', [
        'id_cpr' => $convenioProceso->id_cpr,
        'id_proceso' => $procesoB->id_pro,
        'cupo_maximo_cpr' => 80,
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can change state of a convenio', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_estado']);

    $proceso = crearProcesoConvenio(1);
    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_A',
        'nombre_con' => 'CONVENIO A',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    $convenioProceso = ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-01-01',
        'fecha_fin_cpr' => '2026-03-31',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->call('cambiarEstado', $convenioProceso->id_cpr)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tbl_convenio_proceso', [
        'id_cpr' => $convenioProceso->id_cpr,
        'estado_cpr' => 'DESHABILITADO',
        'id_usuario_actualizador' => $usuario->id_usu,
    ]);
});

test('authorized users can delete a convenio without related records', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_eliminar']);

    $proceso = crearProcesoConvenio(1);
    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_A',
        'nombre_con' => 'CONVENIO A',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    $convenioProceso = ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-01-01',
        'fecha_fin_cpr' => '2026-03-31',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->call('eliminar', $convenioProceso->id_cpr)
        ->assertHasNoErrors();

    $convenioProcesoEliminado = ConvenioProceso::withTrashed()->find($convenioProceso->id_cpr);

    expect($convenioProcesoEliminado)->not->toBeNull();
    expect($convenioProcesoEliminado?->id_usuario_eliminador)->toBe($usuario->id_usu);
});

test('convenio validates duplicate code and duplicate process assignment', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosConvenio($usuario, ['convenio_indice', 'convenio_crear']);

    $proceso = crearProcesoConvenio(1);

    $convenio = Convenio::query()->create([
        'codigo_con' => 'CONV_A',
        'nombre_con' => 'CONVENIO A',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-01-01',
        'fecha_fin_cpr' => '2026-03-31',
        'cupo_maximo_cpr' => 50,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.convenios')
        ->set('convenioForm.codigo', 'CONV_A')
        ->set('convenioForm.nombre', 'Convenio nuevo')
        ->set('convenioForm.tipo_beneficio', 'DESCUENTO')
        ->set('convenioForm.monto_descuento', '50')
        ->set('convenioForm.id_proceso', (string) $proceso->id_pro)
        ->set('convenioForm.fecha_inicio', '2026-01-01')
        ->set('convenioForm.fecha_fin', '2026-03-31')
        ->set('convenioForm.cupo_maximo', '10')
        ->call('guardar')
        ->assertHasErrors(['convenioForm.codigo']);
});
