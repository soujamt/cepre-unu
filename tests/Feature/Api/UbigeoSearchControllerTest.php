<?php

use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can search ubigeos by api', function () {
    $usuario = Usuario::factory()->create();

    Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->getJson(route('api.ubigeo.search', ['query' => 'call']))
        ->assertOk()
        ->assertJsonFragment([
            'value' => '250101',
            'label' => '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA',
        ]);
});

test('ubigeo search returns an empty array for short queries', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->getJson(route('api.ubigeo.search', ['query' => 'c']))
        ->assertOk()
        ->assertExactJson([]);
});

test('authenticated users can resolve a ubigeo by value', function () {
    $usuario = Usuario::factory()->create();

    Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->getJson(route('api.ubigeo.search', ['value' => '250101']))
        ->assertOk()
        ->assertExactJson([
            'value' => '250101',
            'label' => '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA',
        ]);
});
