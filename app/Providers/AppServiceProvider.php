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
use Illuminate\Support\Facades\Route;
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
        $this->registrarRutasDeCatalogo();
    }

    /**
     * Route::catalogo(): rutas de un mantenedor (listado, registro, edición, cambio de
     * estado y eliminación), cada una con su permiso, por ejemplo carrera_indice o
     * carrera_crear.
     */
    private function registrarRutasDeCatalogo(): void
    {
        Route::macro('catalogo', function (string $uri, string $nombre, string $permiso, string $controlador, string $parametro): void {
            Route::get($uri, [$controlador, 'index'])->middleware("permiso:{$permiso}_indice")->name("{$nombre}.index");
            Route::post($uri, [$controlador, 'store'])->middleware("permiso:{$permiso}_crear")->name("{$nombre}.store");
            Route::put("{$uri}/{{$parametro}}", [$controlador, 'update'])->middleware("permiso:{$permiso}_editar")->name("{$nombre}.update");
            Route::patch("{$uri}/{{$parametro}}/estado", [$controlador, 'estado'])->middleware("permiso:{$permiso}_estado")->name("{$nombre}.estado");
            Route::delete("{$uri}/{{$parametro}}", [$controlador, 'destroy'])->middleware("permiso:{$permiso}_eliminar")->name("{$nombre}.destroy");
        });
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
