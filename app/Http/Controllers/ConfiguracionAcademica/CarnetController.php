<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Http\Controllers\Controller;
use App\Models\Parametro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Datos que se imprimen en todos los carnets: director, cargo, nota y la imagen del sello y la firma.
 */
class CarnetController extends Controller
{
    public const RUTA_FIRMA = 'carnet/firma-sello.png';

    public function edit(): Response
    {
        return Inertia::render('configuracion-academica/carnet', [
            'carnet' => [
                'director_nombre' => (string) Parametro::valor('carnet_director_nombre', ''),
                'director_cargo' => (string) Parametro::valor('carnet_director_cargo', ''),
                'nota' => (string) Parametro::valor('carnet_nota', ''),
            ],
            'firmaActual' => $this->firmaActual(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'director_nombre' => ['required', 'string', 'max:120'],
            'director_cargo' => ['required', 'string', 'max:120'],
            'nota' => ['nullable', 'string', 'max:200'],
            'firma' => ['nullable', 'image', 'mimes:png', 'max:1024'],
        ], [
            'director_nombre.required' => 'Ingrese el nombre del director.',
            'director_nombre.max' => 'El nombre no debe superar los 120 caracteres.',
            'director_cargo.required' => 'Ingrese el cargo del director.',
            'director_cargo.max' => 'El cargo no debe superar los 120 caracteres.',
            'nota.max' => 'La nota no debe superar los 200 caracteres.',
            'firma.image' => 'La firma debe ser una imagen PNG.',
            'firma.mimes' => 'La firma debe ser una imagen PNG.',
            'firma.max' => 'La imagen debe pesar como máximo 1 MB.',
        ]);

        Parametro::guardar('carnet_director_nombre', mb_strtoupper(trim($datos['director_nombre']), 'UTF-8'));
        Parametro::guardar('carnet_director_cargo', trim($datos['director_cargo']));
        Parametro::guardar('carnet_nota', trim((string) ($datos['nota'] ?? '')) ?: null);

        if ($request->hasFile('firma')) {
            Storage::disk('local')->put(self::RUTA_FIRMA, (string) file_get_contents($request->file('firma')->getRealPath()));
            Parametro::guardar('carnet_firma_sello', self::RUTA_FIRMA);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración del carnet guardada.']);

        return back();
    }

    /**
     * Imagen guardada de la firma como data URI, para la vista previa.
     */
    private function firmaActual(): ?string
    {
        $ruta = Parametro::valor('carnet_firma_sello');

        if ($ruta === null || ! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) Storage::disk('local')->get($ruta));
    }
}
