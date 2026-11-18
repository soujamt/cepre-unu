<?php

use App\Models\Inscripcion;
use App\Traits\AuthorizesPermisos;
use App\Traits\InscripcionCrear\GestionaAsistenteInscripcion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Nueva inscripción')]
class extends Component
{
    use AuthorizesPermisos;
    use GestionaAsistenteInscripcion;
    use WithFileUploads;

    public function mount(?string $inscripcion = null): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
        $this->hidratarCatalogos();

        if ($inscripcion !== null) {
            $idInscripcion = decode($inscripcion);

            abort_unless($idInscripcion !== null, 404);

            $this->cargarInscripcionExistente($idInscripcion);
        }
    }

    private function cargarInscripcionExistente(int $idInscripcion): void
    {
        $inscripcion = Inscripcion::query()
            ->with([
                'persona.ubigeoNacimiento',
                'persona.ubigeoDomicilio',
                'proceso',
                'sede',
                'modalidadAdmision',
                'carrera',
                'turno',
                'paisColegio',
                'colegio.ubigeo',
                'obligacionesPago.reglaPago.conceptoPago',
                'obligacionesPago.pagos.importacionBancoDetalle',
            ])
            ->findOrFail($idInscripcion);

        $persona = $inscripcion->persona;

        if ($persona === null) {
            abort(404);
        }

        $this->inscripcionId = $inscripcion->id_ins;
        $this->personaId = $persona->id_per;
        $this->cargarPersonaDesdeSistema($persona);
        $this->cargarInformacionGeneralExistente($inscripcion);
        $this->cargarPostulacionExistente($inscripcion);
        $this->cargarPagoExistente($inscripcion);
        $this->pasoActual = $this->pagoValidado ? 4 : 1;
    }

    public function esModoPublico(): bool
    {
        return false;
    }

    public function volverListado(): void
    {
        $this->redirect(route('inscripciones.index'), navigate: true);
    }

    protected function autorizarSubidaFoto(): void
    {
        $this->autorizarPermiso('inscripcion', 'crear');
    }

    protected function despuesDeRegistrar(?Inscripcion $inscripcion): void
    {
        $this->redirect(
            auth()->user()?->hasPermisoAccion('inscripcion_indice') && $inscripcion !== null
                ? route('inscripciones.detalle', encode($inscripcion->id_ins))
                : route('inscripciones.index'),
            navigate: true,
        );
    }
};
