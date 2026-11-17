<?php

namespace App\Http\Controllers;

use App\Models\FotoPostulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoPostulanteController extends Controller
{
    /**
     * Sirve la foto de un postulante solo a usuarios con acceso a fotos o inscripciones.
     */
    public function __invoke(Request $request, string $documento): StreamedResponse
    {
        $usuario = $request->user();

        abort_unless(
            $usuario !== null && collect(['foto_indice', 'inscripcion_indice', 'inscripcion_crear'])
                ->contains(fn (string $permiso): bool => $usuario->hasPermisoAccion($permiso)),
            403,
        );

        $foto = FotoPostulante::deDocumento($documento);

        abort_if($foto === null || ! Storage::disk(FotoPostulante::DISCO)->exists($foto->archivo_fot), 404);

        return Storage::disk(FotoPostulante::DISCO)->response($foto->archivo_fot, "{$foto->numero_documento_fot}.jpg", [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
