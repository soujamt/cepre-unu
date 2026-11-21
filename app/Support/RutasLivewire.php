<?php

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Livewire\Mechanisms\HandleRouting\LivewirePageController;

/**
 * Rutas que todavía son pantallas Livewire, como expresiones regulares para el navegador.
 *
 * Mientras dura la migración a Inertia, una pantalla Livewire solo puede abrir otra
 * Livewire con wire:navigate, y una de React solo puede abrir otra de React con
 * <Link>. Para cualquier otro destino se hace una carga completa de la página.
 */
class RutasLivewire
{
    /**
     * @var list<string>|null
     */
    private static ?array $patrones = null;

    /**
     * @return list<string>
     */
    public static function patrones(): array
    {
        return self::$patrones ??= collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $ruta): bool => $ruta->getActionName() === LivewirePageController::class
                || str_starts_with($ruta->getActionName(), LivewirePageController::class.'@'))
            ->map(fn (Route $ruta): string => self::aExpresion($ruta->uri()))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * "inscripciones/{inscripcion}/detalle" pasa a "^/inscripciones/[^/]+/detalle/?$".
     */
    private static function aExpresion(string $uri): string
    {
        $segmentos = collect(explode('/', trim($uri, '/')))
            ->map(fn (string $segmento): string => match (true) {
                str_starts_with($segmento, '{') && str_ends_with($segmento, '?}') => '[^/]*',
                str_starts_with($segmento, '{') => '[^/]+',
                default => preg_quote($segmento, '/'),
            })
            ->implode('/');

        return '^/'.$segmentos.'/?$';
    }
}
