<?php

use App\Models\Accion;
use App\Models\Parametro;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

function otorgarPermisosCarnet(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accion = Accion::query()->where('clave_accion', Str::afterLast($clave, '_'))->firstOrFail();
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => Str::beforeLast($clave, '_')],
            ['nombre_permiso' => Str::upper($clave), 'estado_permiso' => 'HABILITADO'],
        );
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

test('authorized users can see the carnet configuration and its sample', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarnet($usuario, ['parametro_indice']);

    $this->actingAs($usuario)->get(route('carnet.configuracion'))->assertOk()->assertSee('DIRECTOR GENERAL CEPRE-UNU');
    $this->actingAs($usuario)->get(route('carnet.muestra'))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('users without permiso cannot see the carnet configuration', function () {
    $this->actingAs(Usuario::factory()->create())->get(route('carnet.configuracion'))->assertForbidden();
    $this->actingAs(Usuario::factory()->create())->get(route('carnet.muestra'))->assertForbidden();
});

test('the director data and signature image are saved', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarnet($usuario, ['parametro_indice', 'parametro_editar']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.carnet')
        ->set('carnetForm.director_nombre', 'dr. juan pérez')
        ->set('carnetForm.director_cargo', 'DIRECTOR GENERAL CEPRE-UNU')
        ->set('carnetForm.nota', 'Válido para el ingreso al campus.')
        ->set('firma', UploadedFile::fake()->image('firma.png', 300, 150))
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Configuración del carnet guardada.', type: 'success');

    expect(Parametro::valor('carnet_director_nombre'))->toBe('DR. JUAN PÉREZ')
        ->and(Parametro::valor('carnet_nota'))->toBe('Válido para el ingreso al campus.');

    Storage::disk('local')->assertExists(Parametro::valor('carnet_firma_sello'));
});

test('the signature must be a png image', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarnet($usuario, ['parametro_indice', 'parametro_editar']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.carnet')
        ->set('carnetForm.director_nombre', 'JUAN PÉREZ')
        ->set('firma', UploadedFile::fake()->image('firma.jpg'))
        ->call('guardar')
        ->assertHasErrors('firma');

    expect(Parametro::valor('carnet_firma_sello'))->toBeNull();
});

test('users without editar permiso cannot save the carnet configuration', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosCarnet($usuario, ['parametro_indice']);

    $this->actingAs($usuario);

    Livewire::test('pages::configuracion-academica.carnet')
        ->set('carnetForm.director_nombre', 'JUAN PÉREZ')
        ->call('guardar')
        ->assertForbidden();
});
