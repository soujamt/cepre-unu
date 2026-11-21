<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class SesionController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('auth/login');
    }

    /**
     * Inicia sesión y abre la página que se quería visitar (o el inicio).
     *
     * Se usa una visita completa (Inertia::location) porque esa página puede ser
     * una pantalla Livewire y porque así el navegador empieza limpio.
     */
    public function store(LoginRequest $request): Response
    {
        $request->autenticar();
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bienvenido de nuevo']);

        return Inertia::location(redirect()->intended(route('inicio')));
    }

    /**
     * Cierra la sesión y lleva al inicio de sesión con una carga completa, para no
     * dejar en el navegador datos de la sesión anterior.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sesión cerrada correctamente']);

        return Inertia::location(route('login'));
    }
}
