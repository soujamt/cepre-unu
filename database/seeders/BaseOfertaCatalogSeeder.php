<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseOfertaCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SedeSeeder::class,
            CarreraSeeder::class,
            LugarInscripcionSeeder::class,
            ModalidadAdmisionSeeder::class,
            ModalidadLugarInscripcionSeeder::class,
            SedeCarreraSeeder::class,
            OfertaBaseSeeder::class,
        ]);
    }
}
