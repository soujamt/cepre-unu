<?php

namespace App\Http\Controllers;

use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class VerificacionCarnetController extends Controller
{
    /**
     * Página pública a la que lleva el QR del carnet.
     */
    public function __invoke(string $token): View
    {
        $inscripcion = Inscripcion::query()
            ->with(['persona', 'proceso', 'sede', 'carrera', 'turno'])
            ->where('token_verificacion_ins', $token)
            ->firstOrFail();

        $persona = $inscripcion->persona;
        $documento = (string) $persona?->numero_documento_per;

        return view('verificacion-carnet', [
            'esValido' => $inscripcion->estado_ins === EstadoInscripcion::Activo,
            'nombre' => trim($persona?->nombres_per.' '.$persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per),
            'documento' => Str::mask($documento, '*', 0, max(strlen($documento) - 4, 0)),
            'proceso' => $inscripcion->proceso?->nombre_pro,
            'sede' => $inscripcion->sede?->nombre_sed,
            'carrera' => $inscripcion->carrera?->nombre_car,
            'turno' => $inscripcion->turno?->nombre_tur,
        ]);
    }
}
