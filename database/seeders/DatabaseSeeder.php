<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            TurnoSeeder::class,
            TipoExpedienteSeeder::class,
            ConceptoPagoSeeder::class,
            TipoDocumentoSeeder::class,
            TipoColegioSeeder::class,
        ]);

        Usuario::query()->updateOrCreate([
            'usuario_usu' => 'testuser',
        ], [
            'correo_usu' => 'test@unu.edu.pe',
            'nombres_usu' => 'TEST USER',
            'clave_usu' => 'password',
        ]);
    }
}
