<?php

namespace App\Support\Inscripcion;

use App\Enums\EstadoInscripcion;
use App\Http\Controllers\DocumentoInscripcionController;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Support\InscripcionPagoManager;
use App\Traits\InscripcionCrear\GestionaAsistenteInscripcion;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Inscripción en línea del postulante, sin iniciar sesión. Usa el mismo asistente del
 * personal, pero todo se ata al DNI del pago validado, no se sube foto, no se permite
 * una segunda inscripción en el proceso y la inscripción queda en revisión.
 */
class AsistentePortal extends AsistenteInscripcion
{
    use GestionaAsistenteInscripcion {
        buscarPagosPasoPago as protected buscarPagosAsistente;
        confirmarPasoPago as protected confirmarPagoAsistente;
        guardarPasoPersonal as protected guardarPersonalAsistente;
        buscarDatosPersona as protected buscarDatosPersonaAsistente;
        guardarPasoGeneral as protected guardarGeneralAsistente;
        guardarPasoPostulacion as protected guardarPostulacionAsistente;
        cancelarYReiniciarWizard as protected reiniciarAsistente;
    }

    private const INTENTOS_POR_MINUTO = 8;

    /**
     * DNI con el que se encontró el pago; el resto del asistente queda atado a él.
     */
    public string $documentoPagado = '';

    public ?int $inscripcionRegistradaId = null;

    /**
     * El postulante no confirma duplicados: no puede tener dos inscripciones en el proceso.
     *
     * @return list<string>
     */
    public function accionesPermitidas(): array
    {
        return array_values(array_diff(parent::accionesPermitidas(), ['confirmarGuardadoDuplicado', 'cancelarDuplicadoPostulacion']));
    }

    public function esModoPublico(): bool
    {
        return true;
    }

    public function volverListado(): void
    {
        $this->redirect(route('publico.portal'));
    }

    public function buscarPagosPasoPago(InscripcionPagoManager $manager): void
    {
        if (! $this->permitirIntento('pago')) {
            $this->addError('pagoForm.numero_operacion', 'Demasiados intentos. Espere un minuto e intente de nuevo.');

            return;
        }

        $this->buscarPagosAsistente($manager);

        $this->documentoPagado = $this->busquedaPagoRealizada
            ? (preg_replace('/\D+/', '', (string) $this->pagoForm['numero_documento']) ?? '')
            : '';
    }

    public function confirmarPasoPago(): void
    {
        if (! $this->busquedaPagoRealizada || $this->documentoPagado === '') {
            $this->addError('pagoForm.numero_operacion', 'Primero verifique su pago.');

            return;
        }

        $this->pagoForm['numero_documento'] = $this->documentoPagado;
        $this->confirmarPagoAsistente();

        if ($this->pagoValidado) {
            $this->personaForm['numero_documento'] = $this->documentoPagado;
        }
    }

    public function updatedPersonaFormNumeroDocumento(string $valor): void
    {
        $this->personaForm['numero_documento'] = $this->documentoPagado;
    }

    public function buscarDatosPersona(): void
    {
        if (! $this->pagoValidado || ! $this->permitirIntento('datos')) {
            return;
        }

        $this->personaForm['numero_documento'] = $this->documentoPagado;
        $this->buscarDatosPersonaAsistente();
    }

    public function guardarPasoPersonal(): void
    {
        if (! $this->pagoValidado) {
            return;
        }

        $this->personaForm['numero_documento'] = $this->documentoPagado;
        $this->pagoForm['numero_documento'] = $this->documentoPagado;
        $this->guardarPersonalAsistente();
    }

    public function guardarPasoGeneral(): void
    {
        if (! $this->pagoValidado || $this->personaId === null) {
            return;
        }

        $this->guardarGeneralAsistente();
    }

    public function guardarPasoPostulacion(): void
    {
        if (! $this->pagoValidado || $this->personaId === null) {
            return;
        }

        $proceso = $this->procesoPostulacionActivo();
        $existente = $proceso === null ? null : Inscripcion::query()
            ->where('id_persona', $this->personaId)
            ->where('id_proceso', $proceso->id_pro)
            ->where('estado_ins', '!=', EstadoInscripcion::Anulado->value)
            ->first();

        if ($existente !== null) {
            $this->addError('confirmacionDatos', "Ya tiene una inscripción en este proceso (código {$existente->codigo_ins}). Consulte su inscripción o acérquese al CEPRE-UNU.");

            return;
        }

        $this->guardarPostulacionAsistente();
    }

    public function cancelarYReiniciarWizard(): void
    {
        $this->documentoPagado = '';
        $this->inscripcionRegistradaId = null;
        $this->reiniciarAsistente();
    }

    /**
     * Las fotos las sube solo el personal.
     */
    public function puedeCambiarFoto(): bool
    {
        return false;
    }

    public function fuenteFotoPostulante(FotoPostulante $foto): string
    {
        return (string) $foto->comoDataUri();
    }

    /**
     * Datos del portal: la inscripción registrada y los enlaces firmados a sus documentos.
     *
     * @return array<string, mixed>
     */
    public function vista(): array
    {
        $inscripcion = $this->inscripcionRegistradaId === null
            ? null
            : Inscripcion::query()->with(['proceso', 'sede', 'carrera', 'turno', 'procesoAula.aula'])->find($this->inscripcionRegistradaId);

        return [
            ...parent::vista(),
            'registrada' => $inscripcion === null ? null : [
                'codigo' => $inscripcion->codigo_ins,
                'estado' => $inscripcion->enRevision() ? 'EN REVISIÓN' : $inscripcion->estado_ins->value,
                'proceso' => $inscripcion->proceso?->nombre_pro,
                'sede' => $inscripcion->sede?->nombre_sed,
                'carrera' => $inscripcion->carrera?->nombre_car,
                'turno' => $inscripcion->turno?->nombre_tur,
                'aula' => $inscripcion->procesoAula?->aula?->nombre_aul,
                'documentos' => DocumentoInscripcionController::enlacesPublicos($inscripcion),
            ],
        ];
    }

    protected function autorizarSubidaFoto(): void
    {
        abort(403);
    }

    /**
     * @return array{origen_ins: string, requiere_revision_ins: bool}
     */
    protected function atributosInscripcionNueva(): array
    {
        return ['origen_ins' => 'PORTAL', 'requiere_revision_ins' => true];
    }

    protected function despuesDeRegistrar(?Inscripcion $inscripcion): void
    {
        $this->inscripcionRegistradaId = $inscripcion?->id_ins;
    }

    private function permitirIntento(string $accion): bool
    {
        $clave = "portal-{$accion}:".request()->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_POR_MINUTO)) {
            return false;
        }

        RateLimiter::hit($clave, 60);

        return true;
    }
}
