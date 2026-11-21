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

/**
 * @param  array{0: string, 1: string, 2: string, 3: string}  $datos  Código, departamento, provincia y distrito.
 */
function crearUbigeoBusqueda(array $datos): void
{
    [$codigo, $departamento, $provincia, $distrito] = $datos;

    Ubigeo::query()->create([
        'id_ubi' => $codigo,
        'codigo_departamento_ubi' => substr($codigo, 0, 2),
        'codigo_provincia_ubi' => substr($codigo, 2, 2),
        'codigo_distrito_ubi' => substr($codigo, 4, 2),
        'nombre_departamento_ubi' => $departamento,
        'nombre_provincia_ubi' => $provincia,
        'nombre_distrito_ubi' => $distrito,
        'estado_ubi' => 'HABILITADO',
    ]);
}

test('ubigeo search offers the districts of Ucayali when there is no search text', function () {
    crearUbigeoBusqueda(['150101', 'LIMA', 'LIMA', 'LIMA']);
    crearUbigeoBusqueda(['250102', 'UCAYALI', 'CORONEL PORTILLO', 'CAMPOVERDE']);
    crearUbigeoBusqueda(['250101', 'UCAYALI', 'CORONEL PORTILLO', 'CALLERIA']);

    $this->getJson(route('api.ubigeo.search', ['query' => 'c']))
        ->assertOk()
        ->assertExactJson([
            ['value' => '250101', 'label' => '250101 | UCAYALI / CORONEL PORTILLO / CALLERIA'],
            ['value' => '250102', 'label' => '250102 | UCAYALI / CORONEL PORTILLO / CAMPOVERDE'],
        ]);
});

test('ubigeo search lists Ucayali before other departments', function () {
    crearUbigeoBusqueda(['010101', 'AMAZONAS', 'CHACHAPOYAS', 'CHACHAPOYAS']);
    crearUbigeoBusqueda(['250101', 'UCAYALI', 'CORONEL PORTILLO', 'CALLERIA']);
    crearUbigeoBusqueda(['250105', 'UCAYALI', 'CORONEL PORTILLO', 'YARINACOCHA']);

    $this->getJson(route('api.ubigeo.search', ['query' => 'cha']))
        ->assertOk()
        ->assertJsonPath('0.value', '250105')
        ->assertJsonPath('1.value', '010101');
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
