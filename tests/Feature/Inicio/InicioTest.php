<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
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

    $this->actingAs(Usuario::query()->where('usuario_usu', 'superadmin')->sole())
        ->get(route('inicio'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inicio')
            ->where('proceso.nombre', '2026-1')
            ->where('estadisticas', ['total' => 50, 'activas' => 49, 'pendientes' => 0, 'anuladas' => 1, 'pagoPendiente' => 1])
            ->where('actividadHoy.inscripciones', 1)
            ->where('alertas.0.detalle', '1 inscripción con saldo pendiente o pago parcial.')
            ->has('recientes', 6)
            ->where('recientes.0.id', encode($inscripciones->last()->id_ins))
            ->where('recientes.0.estado', 'Activo'));
});

test('the dashboard explains when there is no enabled process', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('inicio'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inicio')
            ->where('proceso', null)
            ->where('estadisticas', null)
            ->where('alertas', [])
            ->where('recientes', []));
});
