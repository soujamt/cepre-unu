<?php

use App\Models\Colegio;
use App\Models\TipoColegio;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can search colegios by api', function () {
    $usuario = Usuario::factory()->create();

    $tipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PUB',
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ]);

    $tipoColegioPrivado = TipoColegio::query()->create([
        'codigo_tco' => 'PRI',
        'nombre_tco' => 'PRIVADO',
        'estado_tco' => 'HABILITADO',
    ]);

    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'direccion_col' => 'AVENIDA CENTENARIO 100',
        'estado_col' => 'HABILITADO',
    ]);

    Colegio::query()->create([
        'id_col' => '0915390',
        'codigo_modular_col' => '0915390',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD PRIVADO',
        'id_tipo_colegio' => $tipoColegioPrivado->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->getJson(route('api.colegio.search', [
            'query' => 'padre abad',
            'id_tipo_colegio' => $tipoColegio->id_tco,
        ]))
        ->assertOk()
        ->assertJsonFragment([
            'value' => '0915389',
            'label' => '0915389 | AGROPECUARIO PADRE ABAD | UCAYALI / CORONEL PORTILLO / CALLERIA',
            'description' => 'Direccion: AVENIDA CENTENARIO 100',
        ])
        ->assertJsonMissing([
            'value' => '0915390',
        ]);
});

test('colegio search returns an empty array for short queries', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->getJson(route('api.colegio.search', ['query' => 'a']))
        ->assertOk()
        ->assertExactJson([]);
});

test('authenticated users can resolve a colegio by value', function () {
    $usuario = Usuario::factory()->create();

    $tipoColegio = TipoColegio::query()->create([
        'codigo_tco' => 'PUB',
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ]);

    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    Colegio::query()->create([
        'id_col' => '0915389',
        'codigo_modular_col' => '0915389',
        'nombre_col' => 'AGROPECUARIO PADRE ABAD',
        'id_tipo_colegio' => $tipoColegio->id_tco,
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_col' => 'HABILITADO',
    ]);

    $this->actingAs($usuario)
        ->getJson(route('api.colegio.search', ['value' => '0915389']))
        ->assertOk()
        ->assertExactJson([
            'value' => '0915389',
            'label' => '0915389 | AGROPECUARIO PADRE ABAD | UCAYALI / CORONEL PORTILLO / CALLERIA',
        ]);
});
