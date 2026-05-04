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
            SedeSeeder::class,
            AulaSeeder::class,
            CarreraSeeder::class,
            LugarInscripcionSeeder::class,
            ModalidadAdmisionSeeder::class,
            ModalidadLugarInscripcionSeeder::class,
            SedeCarreraSeeder::class,
            OfertaBaseSeeder::class,
            ConfiguracionAcademicaInicialSeeder::class,
        ]);

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
