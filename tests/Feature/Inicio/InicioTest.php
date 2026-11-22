<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\Usuario;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('the dashboard summarizes the enabled process', function () {
    Artisan::call('db:seed', ['--class' => DevelopmentDataSeeder::class, '--no-interaction' => true]);

    $inscripciones = Inscripcion::query()->orderBy('id_ins')->get();
    $inscripciones->first()->update(['estado_ins' => EstadoInscripcion::Anulado->value]);
    InscripcionObligacionPago::query()
        ->where('id_inscripcion', $inscripciones->get(1)->id_ins)
        ->update(['estado_iop' => EstadoObligacionPago::Parcial->value]);

    $documentoConFoto = $inscripciones->last()->persona->numero_documento_per;
    FotoPostulante::query()->create(['numero_documento_fot' => $documentoConFoto, 'archivo_fot' => "fotos/{$documentoConFoto}.jpg"]);

    $this->actingAs(Usuario::query()->where('usuario_usu', 'superadmin')->sole())
        ->get(route('inicio'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inicio')
            ->where('proceso.nombre', '2026-I')
            ->where('resumen.total', 50)
            ->where('resumen.activas', 49)
            ->where('resumen.anuladas', 1)
            ->where('resumen.enRevision', 0)
            ->where('resumen.conSaldo', 1)
            ->where('resumen.pagoParcial', 1)
            ->where('graficos.porEstado.0', ['clave' => 'ACTIVO', 'nombre' => 'ACTIVO', 'total' => 49])
            ->has('graficos.porDia', 14)
            ->where('graficos.porDia.13.fecha', now()->toDateString())
            ->where('graficos.porDia.13.total', 1)
            ->where('graficos.porEstadoPago', fn ($estados): bool => collect($estados)->firstWhere('clave', 'PARCIAL')['total'] === 1)
            ->has('graficos.recaudacion', fn (AssertableInertia $recaudacion) => $recaudacion->hasAll(['esperado', 'recaudado', 'porcentaje']))
            ->where('graficos.porCarrera', fn ($carreras): bool => collect($carreras)->sum('total') === 50)
            ->where('actividadHoy.inscripciones', 1)
            ->where('alertas.0.detalle', '1 inscripción con saldo pendiente o pago parcial.')
            ->has('recientes', 6)
            ->where('recientes.0.id', encode($inscripciones->last()->id_ins))
            ->where('recientes.0.estado', 'Activo')
            ->where('recientes.0.foto', fn (string $foto): bool => str_starts_with($foto, route('fotos.ver', $documentoConFoto)))
            ->where('recientes.1.foto', null));
});

test('the dashboard explains when there is no enabled process', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('inicio'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inicio')
            ->where('proceso', null)
            ->where('resumen', null)
            ->where('graficos', null)
            ->where('alertas', [])
            ->where('recientes', []));
});
