<?php

use Database\Seeders\ColegioSeeder;
use Database\Seeders\EstadoCivilSeeder;
use Database\Seeders\IdentidadEtnicaSeeder;
use Database\Seeders\LenguaExtranjeraSeeder;
use Database\Seeders\LenguaNativaSeeder;
use Database\Seeders\NacionalidadSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\TipoColegioSeeder;
use Database\Seeders\TipoDiscapacidadSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('loads the excel master catalogs into the database', function (): void {
    $this->seed([
        TipoDocumentoSeeder::class,
        TipoDiscapacidadSeeder::class,
        TipoColegioSeeder::class,
        EstadoCivilSeeder::class,
        PaisSeeder::class,
        NacionalidadSeeder::class,
        UbigeoSeeder::class,
        IdentidadEtnicaSeeder::class,
        LenguaNativaSeeder::class,
        LenguaExtranjeraSeeder::class,
        ColegioSeeder::class,
    ]);

    expect(DB::table('tbl_tipo_documento')->count())->toBe(3);
    expect(DB::table('tbl_tipo_discapacidad')->count())->toBe(25);
    expect(DB::table('tbl_tipo_colegio')->count())->toBe(2);
    expect(DB::table('tbl_estado_civil')->count())->toBe(4);
    expect(DB::table('tbl_pais')->count())->toBe(190);
    expect(DB::table('tbl_nacionalidad')->count())->toBe(202);
    expect(DB::table('tbl_ubigeo')->count())->toBe(1905);
    expect(DB::table('tbl_identidad_etnica')->count())->toBe(9);
    expect(DB::table('tbl_lengua_nativa')->count())->toBe(48);
    expect(DB::table('tbl_lengua_extranjera')->count())->toBe(184);
    expect(DB::table('tbl_colegio')->count())->toBe(12379);

    expect(DB::table('tbl_tipo_documento')->where('codigo_tdo', '3')->value('nombre_tdo'))
        ->toBe('CEDULA DE IDENTIDAD');

    expect(DB::table('tbl_tipo_colegio')->where('codigo_tco', '1')->value('nombre_tco'))
        ->toBe('COLEGIO NACIONAL');

    expect(DB::table('tbl_estado_civil')->where('codigo_eci', 'CASADO(A)')->value('nombre_eci'))
        ->toBe('CASADO(A)');

    expect(DB::table('tbl_pais')->where('codigo_pai', '3')->value('nombre_pai'))
        ->toBe('ALBANIA');

    expect(DB::table('tbl_nacionalidad')->where('codigo_nac', '49')->value('nombre_nac'))
        ->toBe('CONGOLEÑA (COD. 49)');

    expect(DB::table('tbl_ubigeo')->where('id_ubi', '010202')->value('nombre_distrito_ubi'))
        ->toBe('ARAMANGO');

    expect(DB::table('tbl_lengua_nativa')->where('codigo_lna', '1')->value('nombre_lna'))
        ->toBe('ACHUAR');

    expect(DB::table('tbl_lengua_extranjera')->where('codigo_lex', '5')->value('nombre_lex'))
        ->toBe('ALBANIA');

    expect(DB::table('tbl_colegio')->where('id_col', '0411736')->first([
        'nombre_col',
        'direccion_col',
        'id_tipo_colegio',
        'id_ubigeo',
    ]))->toMatchObject([
        'nombre_col' => '86001 SANTA ROSA DE VITERBO',
        'direccion_col' => 'JIRON RAMON CASTILLA 1219',
        'id_tipo_colegio' => 1,
        'id_ubigeo' => '020101',
    ]);
});
