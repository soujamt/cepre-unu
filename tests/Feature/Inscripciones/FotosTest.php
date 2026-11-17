<?php

use App\Models\Accion;
use App\Models\FotoPostulante;
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

function otorgarPermisosFoto(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $permisoClave = Str::before($clave, '_');
        $accion = Accion::query()->where('clave_accion', Str::after($clave, $permisoClave.'_'))->firstOrFail();
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $permisoClave],
            ['nombre_permiso' => Str::upper($permisoClave), 'estado_permiso' => 'HABILITADO'],
        );
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

test('authorized users can see the photos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice']);

    $this->actingAs($usuario)->get(route('fotos.index'))->assertOk()->assertSee('Total fotos');
});

test('users without permiso cannot see the photos page', function () {
    $this->actingAs(Usuario::factory()->create())->get(route('fotos.index'))->assertForbidden();
});

test('photos are uploaded in batch using the dni from the file name and resized to carnet size', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice', 'foto_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.fotos')
        ->set('archivos', [
            UploadedFile::fake()->image('47940211.jpg', 600, 900),
            UploadedFile::fake()->image('foto sin dni.png', 400, 400),
        ])
        ->assertSet('documentos', ['47940211', ''])
        ->call('subirFotos')
        ->assertHasErrors('documentos.1')
        ->set('documentos.1', '61417851')
        ->call('subirFotos')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: '2 foto(s) guardada(s) correctamente.', type: 'success');

    $foto = FotoPostulante::deDocumento('47940211');
    $imagen = imagecreatefromstring(Storage::disk('local')->get($foto->archivo_fot));

    expect(FotoPostulante::query()->count())->toBe(2)
        ->and([imagesx($imagen), imagesy($imagen)])->toBe([300, 400]);
});

test('uploading a photo for the same dni replaces it', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice', 'foto_crear']);

    $this->actingAs($usuario);

    foreach ([1, 2] as $vez) {
        Livewire::test('pages::inscripciones.fotos')
            ->set('archivos', [UploadedFile::fake()->image("47940211-{$vez}.jpg", 300, 400)])
            ->set('documentos.0', '47940211')
            ->call('subirFotos')
            ->assertHasNoErrors();
    }

    expect(FotoPostulante::query()->count())->toBe(1);
});

test('non image files are rejected', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice', 'foto_crear']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.fotos')
        ->set('archivos', [UploadedFile::fake()->create('47940211.pdf', 10, 'application/pdf')])
        ->call('subirFotos')
        ->assertHasErrors('archivos.0');

    expect(FotoPostulante::query()->count())->toBe(0);
});

test('a photo can be deleted and exported', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice', 'foto_crear', 'foto_eliminar', 'foto_exportar_excel']);

    $this->actingAs($usuario);

    $componente = Livewire::test('pages::inscripciones.fotos')
        ->set('archivos', [UploadedFile::fake()->image('47940211.jpg'), UploadedFile::fake()->image('61417851.jpg')])
        ->call('subirFotos');

    $componente->call('exportarFotos')->assertFileDownloaded();

    $componente->call('eliminar', FotoPostulante::deDocumento('47940211')->id_fot)
        ->assertDispatched('toast', message: 'Foto eliminada correctamente.', type: 'success');

    expect(FotoPostulante::query()->count())->toBe(1)
        ->and(Storage::disk('local')->exists('fotos/47940211.jpg'))->toBeFalse();
});

test('the photo image requires a user with access', function () {
    Storage::disk('local')->put('fotos/47940211.jpg', 'imagen');
    FotoPostulante::query()->create(['numero_documento_fot' => '47940211', 'archivo_fot' => 'fotos/47940211.jpg']);

    $this->get(route('fotos.ver', '47940211'))->assertRedirect(route('login'));

    $this->actingAs(Usuario::factory()->create())->get(route('fotos.ver', '47940211'))->assertForbidden();

    $usuario = Usuario::factory()->create();
    otorgarPermisosFoto($usuario, ['foto_indice']);

    $this->actingAs($usuario)->get(route('fotos.ver', '47940211'))->assertOk();
    $this->actingAs($usuario)->get(route('fotos.ver', '99999999'))->assertNotFound();
});
