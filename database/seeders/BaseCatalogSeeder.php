<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermisoModuloSeeder::class,
            TurnoSeeder::class,
            TipoExpedienteSeeder::class,
            ConceptoPagoSeeder::class,
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
    }
}
