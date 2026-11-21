<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Inscripciones\ControladorAsistente;
use App\Support\Inscripcion\AsistentePortal;
use Illuminate\Http\RedirectResponse;

/**
 * Inscripción en línea del postulante con el mismo asistente del personal.
 */
class InscripcionPortalController extends ControladorAsistente
{
    public function crear(): RedirectResponse
    {
        return $this->abrir(AsistentePortal::iniciar());
    }

    protected function modo(): string
    {
        return 'portal';
    }

    protected function clase(): string
    {
        return AsistentePortal::class;
    }

    protected function rutaAsistente(string $token): string
    {
        return route('publico.inscripcion.asistente', $token);
    }

    protected function rutaInicio(): string
    {
        return route('publico.inscripcion');
    }

    protected function pagina(): string
    {
        return 'publico/inscripcion';
    }
}
