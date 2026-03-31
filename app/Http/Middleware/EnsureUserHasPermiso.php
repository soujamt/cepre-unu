<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->hasPermisoAccion($permiso), 403);

        return $next($request);
    }
}
