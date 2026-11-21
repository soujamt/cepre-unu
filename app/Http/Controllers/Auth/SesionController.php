<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SesionController extends Controller
{
    /**
     * Cierra la sesión del usuario y lo lleva al inicio de sesión.
     *
     * Se usa una visita completa (Inertia::location) porque el inicio de sesión
     * puede ser una pantalla que todavía no es de Inertia.
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
