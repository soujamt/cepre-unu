<?php

namespace App\Support\Inscripcion;

use App\Models\Inscripcion;

/**
 * Asistente de inscripción del personal del CEPRE (con sesión iniciada).
 */
class AsistentePersonal extends AsistenteInscripcion
{
    public function esModoPublico(): bool
    {
        return false;
    }

    public function volverListado(): void
    {
        $this->redirect(route('inscripciones.index'));
    }

    protected function autorizarSubidaFoto(): void
    {
        abort_unless(request()->user()?->hasPermisoAccion('inscripcion_crear'), 403);
    }

    protected function despuesDeRegistrar(?Inscripcion $inscripcion): void
    {
        $this->redirect(
            request()->user()?->hasPermisoAccion('inscripcion_indice') && $inscripcion !== null
                ? route('inscripciones.detalle', encode($inscripcion->id_ins))
                : route('inscripciones.index'),
        );
    }
}
