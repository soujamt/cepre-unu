<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class InitialAccessSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::query()->updateOrCreate([
            'usuario_usu' => 'superadmin',
        ], [
            'correo_usu' => 'superadmin@unu.edu.pe',
            'nombres_usu' => 'SUPER ADMINISTRADOR',
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
