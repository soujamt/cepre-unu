<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Models\FotoPostulante;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\Persona;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Artisan::call('db:seed', ['--class' => DevelopmentDataSeeder::class, '--no-interaction' => true]);
});

/**
 * @return Builder<Inscripcion>
 */
function inscripcionesDePrueba(): Builder
{
    return Inscripcion::query()->whereHas('persona', fn ($query) => $query->where('numero_documento_per', 'like', '7123%'));
}

it('loads fictitious applicants in every state of the process', function (): void {
    $this->artisan('cepre:datos-prueba')->assertSuccessful();

    expect(inscripcionesDePrueba()->where('estado_ins', EstadoInscripcion::Activo->value)->count())->toBe(26)
        ->and(inscripcionesDePrueba()->where('estado_ins', EstadoInscripcion::Pendiente->value)->where('requiere_revision_ins', true)->where('origen_ins', 'PORTAL')->count())->toBe(4)
        ->and(inscripcionesDePrueba()->where('estado_ins', EstadoInscripcion::Anulado->value)->count())->toBe(2)
        ->and(inscripcionesDePrueba()->whereHas('obligacionesPago', fn ($query) => $query->where('estado_iop', EstadoObligacionPago::Parcial->value))->count())->toBe(6)
        ->and(ImportacionBancoDetalle::query()->where('numero_documento_ibd', 'like', '7123%')->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)->count())->toBe(11)
        ->and(FotoPostulante::query()->where('numero_documento_fot', 'like', '7123%')->count())->toBe(22);

    $this->artisan('cepre:datos-prueba')
        ->expectsOutputToContain('ya están cargados')
        ->assertSuccessful();

    expect(inscripcionesDePrueba()->count())->toBe(32);
});

it('removes only the test data when cleaning', function (): void {
    $this->artisan('cepre:datos-prueba')->assertSuccessful();
    $this->artisan('cepre:datos-prueba --limpiar')->assertSuccessful();

    expect(inscripcionesDePrueba()->withTrashed()->count())->toBe(0)
        ->and(Persona::withTrashed()->where('numero_documento_per', 'like', '7123%')->count())->toBe(0)
        ->and(ImportacionBancoDetalle::withTrashed()->where('numero_documento_ibd', 'like', '7123%')->count())->toBe(0)
        ->and(Storage::disk('local')->files('fotos'))->toBeEmpty()
        ->and(Inscripcion::query()->where('codigo_ins', 'like', 'MANUAL-INS-%')->count())->toBe(50);
});
