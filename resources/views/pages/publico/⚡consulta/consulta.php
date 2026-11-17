<?php

use App\Http\Controllers\DocumentoInscripcionController;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Consulta de la inscripción por el postulante: reemplaza la hoja que se
 * publicaba para que cada alumno buscara su aula. Pide DNI y N° de operación
 * de uno de sus pagos para no exponer datos solo con el DNI.
 */
new
#[Layout('layouts.auth')]
#[Title('Consultar mi inscripción')]
class extends Component
{
    private const INTENTOS_POR_MINUTO = 8;

    public string $documento = '';

    public string $operacion = '';

    #[Locked]
    public ?int $inscripcionEncontradaId = null;

    public function consultar(): void
    {
        $this->inscripcionEncontradaId = null;

        $this->validate([
            'documento' => ['required', 'regex:/^\d{8,12}$/'],
            'operacion' => ['required', 'regex:/^\d{1,20}$/'],
        ], [
            'documento.required' => 'Ingrese su DNI o CE.',
            'documento.regex' => 'El documento debe tener entre 8 y 12 dígitos.',
            'operacion.required' => 'Ingrese el número de operación de uno de sus pagos.',
            'operacion.regex' => 'El número de operación solo tiene dígitos.',
        ]);

        $clave = 'portal-consulta:'.request()->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_POR_MINUTO)) {
            $this->addError('operacion', 'Demasiados intentos. Espere un minuto e intente de nuevo.');

            return;
        }

        RateLimiter::hit($clave, 60);

        $operacion = ltrim($this->operacion, '0') ?: '0';

        $this->inscripcionEncontradaId = Inscripcion::query()
            ->whereHas('persona', fn (Builder $query) => $query->where('numero_documento_per', $this->documento))
            ->whereHas('obligacionesPago.pagos', fn (Builder $query) => $query->where('numero_operacion_ipa', $operacion))
            ->orderByDesc('id_ins')
            ->value('id_ins');

        if ($this->inscripcionEncontradaId === null) {
            $this->addError('operacion', 'No encontramos una inscripción con ese documento y número de operación.');
        }
    }

    public function limpiar(): void
    {
        $this->reset();
    }

    #[Computed]
    public function inscripcion(): ?Inscripcion
    {
        return $this->inscripcionEncontradaId === null ? null : Inscripcion::query()
            ->with(['persona', 'proceso', 'sede', 'modalidadAdmision', 'carrera', 'turno', 'procesoAula.aula', 'obligacionesPago'])
            ->find($this->inscripcionEncontradaId);
    }

    /**
     * @return array{ficha:string,carnet:?string}|null
     */
    #[Computed]
    public function enlaces(): ?array
    {
        $inscripcion = $this->inscripcion;

        return $inscripcion === null || $inscripcion->estado_ins === App\Enums\EstadoInscripcion::Anulado
            ? null
            : DocumentoInscripcionController::enlacesPublicos($inscripcion);
    }
};
