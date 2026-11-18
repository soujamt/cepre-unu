<?php

use App\Enums\EstadoInscripcion;
use App\Http\Controllers\DocumentoInscripcionController;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Support\InscripcionPagoManager;
use App\Traits\InscripcionCrear\GestionaAsistenteInscripcion;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Inscripción en línea del postulante, sin iniciar sesión. Usa el mismo
 * asistente del personal, pero el avance, la persona y los pagos quedan
 * bloqueados en el servidor y todo se ata al DNI del pago validado.
 */
new
#[Layout('layouts.auth')]
#[Title('Inscripción en línea')]
class extends Component
{
    use GestionaAsistenteInscripcion {
        buscarPagosPasoPago as protected buscarPagosAsistente;
        confirmarPasoPago as protected confirmarPagoAsistente;
        guardarPasoPersonal as protected guardarPersonalAsistente;
        guardarPasoGeneral as protected guardarGeneralAsistente;
        buscarDatosPersona as protected buscarDatosPersonaAsistente;
        guardarPasoPostulacion as protected guardarPostulacionAsistente;
        cancelarYReiniciarWizard as protected reiniciarAsistente;
    }
    use WithFileUploads;

    private const INTENTOS_POR_MINUTO = 8;

    #[Locked]
    public int $pasoActual = 1;

    /**
     * @var array<int, int>
     */
    #[Locked]
    public array $resultadoPagoDetalleIds = [];

    #[Locked]
    public bool $busquedaPagoRealizada = false;

    #[Locked]
    public bool $pagoValidado = false;

    #[Locked]
    public ?int $personaId = null;

    #[Locked]
    public ?int $inscripcionId = null;

    /**
     * DNI con el que se encontró el pago; el resto del asistente queda atado a él.
     */
    #[Locked]
    public string $documentoPagado = '';

    #[Locked]
    public ?int $inscripcionRegistradaId = null;

    public function mount(): void
    {
        $this->hidratarCatalogos();
    }

    public function esModoPublico(): bool
    {
        return true;
    }

    public function volverListado(): void
    {
        $this->redirect(route('publico.portal'), navigate: true);
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

        $proceso = $this->procesoPostulacionActivo;
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

    public function confirmarGuardadoDuplicado(): void
    {
        abort(403);
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
     * @return array{ficha:string,carnet:?string}|null
     */
    #[Computed]
    public function enlacesDocumentos(): ?array
    {
        $inscripcion = $this->inscripcionRegistradaId === null ? null : Inscripcion::query()->find($this->inscripcionRegistradaId);

        return $inscripcion === null ? null : DocumentoInscripcionController::enlacesPublicos($inscripcion);
    }

    #[Computed]
    public function inscripcionRegistrada(): ?Inscripcion
    {
        return $this->inscripcionRegistradaId === null
            ? null
            : Inscripcion::query()->with(['proceso', 'sede', 'carrera', 'turno', 'procesoAula.aula'])->find($this->inscripcionRegistradaId);
    }

    protected function autorizarSubidaFoto(): void
    {
        abort_unless($this->puedeCambiarFoto(), 403);
    }

    /**
     * @return array{origen_ins:string,requiere_revision_ins:bool}
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
};
