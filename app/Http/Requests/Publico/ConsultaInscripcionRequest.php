<?php

namespace App\Http\Requests\Publico;

use App\Models\Inscripcion;
use App\Support\InscripcionPagoManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Consulta de la inscripción por el postulante con su DNI y el N° de operación de
 * uno de sus pagos, para no exponer datos solo con el DNI.
 */
class ConsultaInscripcionRequest extends FormRequest
{
    private const INTENTOS_POR_MINUTO = 8;

    /**
     * Clave del error cuando el pago existe y está libre, pero no hay inscripción.
     */
    public const CLAVE_PAGO_SIN_INSCRIPCION = 'pago_sin_inscripcion';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'documento' => ['required', 'regex:/^\d{8,12}$/'],
            'operacion' => ['required', 'regex:/^\d{1,20}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documento.required' => 'Ingrese su DNI o CE.',
            'documento.regex' => 'El documento debe tener entre 8 y 12 dígitos.',
            'operacion.required' => 'Ingrese el número de operación de uno de sus pagos.',
            'operacion.regex' => 'El número de operación solo tiene dígitos.',
        ];
    }

    /**
     * Busca la inscripción más reciente del documento que tenga un pago con ese
     * número de operación. Limita los intentos por IP para evitar búsquedas masivas.
     *
     * @throws ValidationException
     */
    public function inscripcion(): Inscripcion
    {
        $clave = 'portal-consulta:'.$this->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_POR_MINUTO)) {
            throw ValidationException::withMessages(['operacion' => 'Demasiados intentos. Espere un minuto e intente de nuevo.']);
        }

        RateLimiter::hit($clave, 60);

        $operacion = ltrim($this->string('operacion')->toString(), '0') ?: '0';

        $inscripcion = Inscripcion::query()
            ->with(['persona', 'proceso', 'sede', 'modalidadAdmision', 'carrera', 'turno', 'procesoAula.aula', 'obligacionesPago'])
            ->whereHas('persona', fn (Builder $query) => $query->where('numero_documento_per', $this->string('documento')->toString()))
            ->whereHas('obligacionesPago.pagos', fn (Builder $query) => $query->where('numero_operacion_ipa', $operacion))
            ->orderByDesc('id_ins')
            ->first();

        if ($inscripcion === null && $this->tienePagoSinInscripcion()) {
            throw ValidationException::withMessages([
                self::CLAVE_PAGO_SIN_INSCRIPCION => 'Su pago está registrado, pero aún no completa su inscripción. Use la opción Inscribirme con el mismo DNI y número de operación.',
            ]);
        }

        if ($inscripcion === null) {
            throw ValidationException::withMessages(['operacion' => 'No encontramos una inscripción con ese documento y número de operación.']);
        }

        return $inscripcion;
    }

    /**
     * El documento tiene un pago importado del banco con ese número de operación que todavía
     * no se usó en ninguna inscripción (el postulante pagó, pero no terminó de inscribirse).
     */
    private function tienePagoSinInscripcion(): bool
    {
        return app(InscripcionPagoManager::class)
            ->buscarDetallesDisponibles($this->string('documento')->toString(), $this->string('operacion')->toString())
            ->isNotEmpty();
    }
}
