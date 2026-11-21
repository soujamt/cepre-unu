<?php

namespace App\Http\Controllers\Inscripciones;

use App\Support\Inscripcion\AsistentePersonal;
use Illuminate\Http\RedirectResponse;

/**
 * Asistente de inscripción del personal: nueva inscripción o continuar una pendiente.
 */
class AsistenteInscripcionController extends ControladorAsistente
{
    public function crear(): RedirectResponse
    {
        return $this->abrir(AsistentePersonal::iniciar());
    }

    public function continuar(string $inscripcion): RedirectResponse
    {
        $id = decode($inscripcion);
        abort_if($id === null, 404);

        $asistente = AsistentePersonal::iniciar();
        $asistente->cargarInscripcion($id);

        return $this->abrir($asistente);
    }

    protected function modo(): string
    {
        return 'personal';
    }

    protected function clase(): string
    {
        return AsistentePersonal::class;
    }

    protected function rutaAsistente(string $token): string
    {
        return route('inscripciones.asistente', $token);
    }

    protected function rutaInicio(): string
    {
        return route('inscripciones.crear');
    }

    protected function pagina(): string
    {
        return 'inscripciones/asistente';
    }

    protected function accionesDePregunta(): array
    {
        return ['duplicado-confirmado' => 'confirmarGuardadoDuplicado', 'duplicado-cancelado' => 'cancelarDuplicadoPostulacion'];
    }
}
