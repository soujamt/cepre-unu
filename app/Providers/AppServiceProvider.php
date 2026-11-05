<?php

namespace App\Providers;

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\RolAccion;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('permiso', function (string $clave): bool {
            return auth()->user()?->hasPermisoAccion($clave) ?? false;
        });

        $this->invalidarPermisosAlCambiarSeguridad();
    }

    /**
     * Los permisos del usuario se cachean por solicitud; si en esa misma
     * solicitud cambian roles o permisos, se descarta el caché.
     */
    private function invalidarPermisosAlCambiarSeguridad(): void
    {
        foreach ([Rol::class, Permiso::class, Accion::class, PermisoAccion::class, RolAccion::class, UsuarioRol::class] as $modelo) {
            $modelo::saved(fn () => Usuario::olvidarPermisosEnCache());
            $modelo::deleted(fn () => Usuario::olvidarPermisosEnCache());
        }
    }
}
