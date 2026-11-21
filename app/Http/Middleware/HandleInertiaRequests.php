<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Vista raíz que se carga en la primera visita a una página Inertia.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Datos compartidos con todas las páginas: la aplicación, el usuario y sus permisos.
     *
     * Los avisos (toasts) no van aquí: se envían con Inertia::flash() para que no
     * queden guardados en el historial del navegador.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'nombre' => config('app.name'),
                'version' => config('app.version'),
            ],
            'auth' => fn (): array => $this->datosDeSesion($request->user()),
        ];
    }

    /**
     * @return array{user: array{nombre: string, correo: string, iniciales: string}|null, permisos: array{administrador: bool, claves: list<string>}}
     */
    private function datosDeSesion(?Usuario $usuario): array
    {
        if ($usuario === null) {
            return [
                'user' => null,
                'permisos' => ['administrador' => false, 'claves' => []],
            ];
        }

        return [
            'user' => [
                'nombre' => $usuario->nombreVisible(),
                'correo' => $usuario->correoOculto(),
                'iniciales' => $usuario->iniciales(),
            ],
            'permisos' => $usuario->resumenPermisos(),
        ];
    }
}
