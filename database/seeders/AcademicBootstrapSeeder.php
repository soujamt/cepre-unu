<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AcademicBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConfiguracionAcademicaInicialSeeder::class,
        ]);
    }
}
