<?php

use App\Models\Inscripcion;
use App\Support\GeneradorCodigoInscripcion;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('the code combines the year and number of the proceso with the correlative of the inscription', function () {
    Artisan::call('db:seed', ['--class' => DevelopmentDataSeeder::class, '--no-interaction' => true]);

    $inscripciones = Inscripcion::query()->orderBy('id_ins')->get();
    $generador = app(GeneradorCodigoInscripcion::class);

    expect($inscripciones)->toHaveCount(50)
        ->and($generador->generar($inscripciones->first()))->toBe('2610001')
        ->and($generador->generar($inscripciones->get(9)))->toBe('2610010')
        ->and($generador->generar($inscripciones->last()))->toBe('2610050');
});
