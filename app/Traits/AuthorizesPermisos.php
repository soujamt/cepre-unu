<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait AuthorizesPermisos
{
    protected function autorizarPermiso(string $modulo, string $accion): void
    {
        abort_unless($this->tienePermiso($modulo, $accion), 403);
    }

    protected function tienePermiso(string $modulo, string $accion): bool
    {
        return Auth::user()?->hasPermisoAccion($this->clavePermiso($modulo, $accion)) ?? false;
    }

    protected function clavePermiso(string $modulo, string $accion): string
    {
        return "{$modulo}_{$accion}";
    }
}
