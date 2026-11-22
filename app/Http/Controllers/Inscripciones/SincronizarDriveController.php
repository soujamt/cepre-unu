<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Support\Drive\SincronizadorDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

/**
 * Sincroniza al momento las hojas DATA y PAGOS del Drive (además de la sincronización
 * automática cada 10 minutos).
 */
class SincronizarDriveController extends Controller
{
    public function __invoke(Request $request, SincronizadorDrive $sincronizador): RedirectResponse
    {
        $proceso = ctype_digit((string) $request->input('proceso')) ? Proceso::query()->find((int) $request->input('proceso')) : null;

        try {
            $resultado = $sincronizador->sincronizar($proceso);
        } catch (RuntimeException $error) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $error->getMessage()]);

            return back();
        }

        $detalle = collect($resultado['hojas'])->map(fn (array $hoja): string => "{$hoja['archivo']} ({$hoja['filas']} filas)")->implode(' y ');
        Inertia::flash('toast', ['type' => 'success', 'message' => "Drive actualizado: {$detalle}."]);

        return back();
    }
}
