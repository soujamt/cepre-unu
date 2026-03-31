<?php

namespace Database\Seeders;

use App\Models\Rol;
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

        $usuario = Usuario::query()->updateOrCreate([
            'usuario_usu' => 'testuser',
        ], [
            'correo_usu' => 'test@unu.edu.pe',
            'nombres_usu' => 'TEST USER',
            'clave_usu' => 'password',
            'estado_usu' => 'HABILITADO',
        ]);

        $rolAdministrador = Rol::query()->updateOrCreate([
            'nombre_rol' => 'ADMINISTRADOR',
        ], [
            'descripcion_rol' => 'Superadministrador con acceso total al sistema.',
            'estado_rol' => 'HABILITADO',
        ]);

        $usuario->roles()->syncWithoutDetaching([$rolAdministrador->id_rol]);
    }
}
