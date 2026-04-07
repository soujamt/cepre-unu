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
use Livewire\Livewire;

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

test('authorized users can access pagos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice']);

    $this->actingAs($usuario)
        ->get(route('pagos.index'))
        ->assertOk()
        ->assertSee('PAGOS');
});

test('authenticated users without permiso cannot access pagos page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('pagos.index'))
        ->assertForbidden();
});

test('authorized users can import a valid bank txt file', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPago($usuario, ['pago_indice', 'pago_crear']);

    $archivo = UploadedFile::fake()->createWithContent('REC10768.TXT', crearTxtBancoValido());

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos')
        ->set('archivo', $archivo)
        ->call('importar')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

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
        'numero_operacion_ibd' => '817041676',
        'codigo_oficina_ibd' => '01068',
        'codigo_canal_ibd' => 'Y%G',
        'monto_ibd' => 30.00,
        'estado_ibd' => 'PENDIENTE',
    ]);
});
