<?php

use App\Models\Inscripcion;
use App\Support\GeneradorCodigoInscripcion;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('the code combines semester, process and the correlative of the inscription within the process', function () {
    Artisan::call('db:seed', ['--class' => DevelopmentDataSeeder::class, '--no-interaction' => true]);

    $inscripciones = Inscripcion::query()->orderBy('id_ins')->get();
    $generador = app(GeneradorCodigoInscripcion::class);

    expect($inscripciones)->toHaveCount(50)
        ->and($generador->generar($inscripciones->first()))->toBe('261ORD0001')
        ->and($generador->generar($inscripciones->get(9)))->toBe('261ORD0010')
        ->and($generador->generar($inscripciones->last()))->toBe('261ORD0050');
});
