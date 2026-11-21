<?php

use App\Models\Accion;
use App\Models\ImportacionBanco;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function otorgarPermisosPago(Usuario $usuario, array $claves): void
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

function crearTxtBancoValido(): string
{
    return collect([
        '0120154598244000PEN20260319001104410100008635',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '02                              00007450332LUCY BETTY FALCON ROJAS              00000000000300000000000000300000000000000000008170416762026031901068Y%G ',
        '03000000002000000000048000000000000048000000000000000000',
    ])
        ->map(fn (string $line): string => str_pad($line, 152, ' '))
        ->implode(PHP_EOL);
}

function crearTxtBancoConDetalleExistente(): string
{
    return collect([
        '0120154598244000PEN20260319001104410100008635',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '03000000001000000000045000000000000045000000000000000000',
    ])
        ->map(fn (string $line): string => str_pad($line, 152, ' '))
        ->implode(PHP_EOL);
}

function crearTxtBancoConDetalleDuplicadoInterno(): string
{
    return collect([
        '0120154598244000PEN20260319001104410100008635',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '03000000002000000000090000000000000090000000000000000000',
    ])
        ->map(fn (string $line): string => str_pad($line, 152, ' '))
        ->implode(PHP_EOL);
}

test('authorized users can access pagos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice']);

    $this->actingAs($usuario)
        ->get(route('pagos.importaciones'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('pagos/importaciones')->has('importaciones.data')->where('previsualizacion', null));
});

test('authenticated users without permiso cannot access pagos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('pagos.importaciones'))
        ->assertForbidden();
});

test('authorized users can import a valid bank txt file', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $archivo = UploadedFile::fake()->createWithContent('REC10768.TXT', crearTxtBancoValido());

    $this->actingAs($usuario)
        ->post(route('pagos.importaciones.previsualizar'), ['archivo' => $archivo])
        ->assertSessionHasNoErrors();

    $this->actingAs($usuario)
        ->get(route('pagos.importaciones'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previsualizacion.valido', true)
            ->where('previsualizacion.nombre_archivo', 'REC10768.TXT')
            ->has('previsualizacion.muestra', 2)
            ->missing('previsualizacion.detalles'));

    $this->actingAs($usuario)
        ->post(route('pagos.importaciones.confirmar'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Archivo TXT importado correctamente.'])
        ->assertSessionMissing('importacion_banco.previsualizacion');

    $importacion = ImportacionBanco::query()->first();

    expect($importacion)->not->toBeNull();
    expect($importacion?->nombre_archivo_iba)->toBe('REC10768.TXT');
    expect($importacion?->moneda_iba)->toBe('PEN');
    expect($importacion?->fecha_lote_iba?->toDateString())->toBe('2026-03-19');
    expect($importacion?->total_registros_iba)->toBe(2);
    expect((float) $importacion?->total_monto_iba)->toBe(480.0);
    expect($importacion?->estado_iba->value)->toBe('PROCESADO');
    expect($importacion?->detalles()->count())->toBe(2);

    $this->assertDatabaseHas('tbl_importacion_banco_detalle', [
        'id_importacion_banco' => $importacion?->id_iba,
        'numero_documento_ibd' => '47467212',
        'numero_operacion_raw_ibd' => '7799041670',
        'numero_operacion_visible_ibd' => '41670',
        'numero_operacion_ibd' => '7799041670',
        'codigo_oficina_ibd' => '0204',
        'codigo_canal_ibd' => 'WNET',
        'monto_ibd' => 450.00,
        'estado_ibd' => 'PENDIENTE',
    ]);

    $this->assertDatabaseHas('tbl_importacion_banco_detalle', [
        'id_importacion_banco' => $importacion?->id_iba,
        'numero_documento_ibd' => '7450332',
        'nombre_depositante_ibd' => 'LUCY BETTY FALCON ROJAS',
        'numero_operacion_raw_ibd' => '817041676',
        'numero_operacion_visible_ibd' => '41676',
        'numero_operacion_ibd' => '817041676',
        'codigo_oficina_ibd' => '01068',
        'codigo_canal_ibd' => 'Y%G',
        'monto_ibd' => 30.00,
        'estado_ibd' => 'PENDIENTE',
    ]);

    $this->actingAs($usuario)
        ->get(route('pagos.importaciones', ['importacion' => $importacion?->id_iba]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('detalle.pagos', fn ($pagos): bool => collect($pagos)->contains('documento', '07450332')));
});

test('the same txt file cannot be imported twice', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC10768.TXT', crearTxtBancoValido())]);
    $this->post(route('pagos.importaciones.confirmar'));

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC10768-COPIA.TXT', crearTxtBancoValido())])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'Este archivo TXT ya fue importado anteriormente.'])
        ->assertSessionMissing('importacion_banco.previsualizacion');

    expect(ImportacionBanco::query()->count())->toBe(1);
    expect(ImportacionBanco::query()->first()?->detalles()->count())->toBe(2);
});

test('invalid txt files are rejected without creating imports', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC-INVALIDO.TXT', 'contenido invalido')])
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast.type', 'error');

    expect(ImportacionBanco::query()->count())->toBe(0);
});

test('txt imports with inconsistent totals are rejected before creating imports', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $contenidoObservado = collect([
        '0120154598244000PEN20260319001104410100008635',
        '02                              00047467212                                     0000000000450000000000000450000000000000000007799041670202603190204WNET ',
        '03000000001000000000040000000000000040000000000000000000',
    ])
        ->map(fn (string $line): string => str_pad($line, 152, ' '))
        ->implode(PHP_EOL);

    $this->actingAs($usuario);

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC-OBSERVADO.TXT', $contenidoObservado)])
        ->assertSessionHas('importacion_banco.previsualizacion.valido', false);

    $this->get(route('pagos.importaciones'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previsualizacion', function ($previsualizacion): bool {
            $json = json_encode($previsualizacion, JSON_UNESCAPED_UNICODE);

            return str_contains($json, 'El monto total no cuadra');
        }));

    $this->post(route('pagos.importaciones.confirmar'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'No se puede importar un TXT con observaciones.']);

    expect(ImportacionBanco::query()->count())->toBe(0);
});

test('txt imports with details already imported are rejected before creating duplicate payments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC10768.TXT', crearTxtBancoValido())]);
    $this->post(route('pagos.importaciones.confirmar'))->assertSessionHasNoErrors();

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC10769.TXT', crearTxtBancoConDetalleExistente())])
        ->assertSessionHas('importacion_banco.previsualizacion.valido', false);

    $this->get(route('pagos.importaciones'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previsualizacion', function ($previsualizacion): bool {
            $json = json_encode($previsualizacion, JSON_UNESCAPED_UNICODE);

            return str_contains($json, 'Ya importado') && str_contains($json, '41670');
        }));

    $this->post(route('pagos.importaciones.confirmar'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'No se puede importar un TXT con observaciones.']);

    expect(ImportacionBanco::query()->count())->toBe(1);
});

test('txt imports with duplicate details in the same file are rejected before creating payments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $this->actingAs($usuario);

    $this->post(route('pagos.importaciones.previsualizar'), ['archivo' => UploadedFile::fake()->createWithContent('REC-DUPLICADO.TXT', crearTxtBancoConDetalleDuplicadoInterno())])
        ->assertSessionHas('importacion_banco.previsualizacion.valido', false);

    $this->get(route('pagos.importaciones'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('previsualizacion', function ($previsualizacion): bool {
            $json = json_encode($previsualizacion, JSON_UNESCAPED_UNICODE);

            return str_contains($json, 'Duplicado en el TXT') && str_contains($json, '41670');
        }));

    $this->post(route('pagos.importaciones.confirmar'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'No se puede importar un TXT con observaciones.']);

    expect(ImportacionBanco::query()->count())->toBe(0);
});
