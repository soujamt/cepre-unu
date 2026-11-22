<?php

namespace App\Http\Requests\Inscripciones;

use App\Models\Inscripcion;
use App\Support\Inscripcion\CambioPostulacion;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de sede, carrera o turno de una inscripción, con el motivo obligatorio.
 * Las opciones se validan contra la oferta del proceso de la propia inscripción.
 */
class ActualizarPostulacionRequest extends FormRequest
{
    private ?Inscripcion $registro = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $cambio = app(CambioPostulacion::class);
        $inscripcion = $this->inscripcion();

        return [
            'sede' => ['required', 'integer', function (string $atributo, mixed $valor, Closure $falla) use ($cambio, $inscripcion): void {
                if (! $cambio->sedeValida($inscripcion, (int) $valor)) {
                    $falla('La sede no tiene carreras habilitadas en el proceso de la inscripción.');
                }
            }],
            'carrera' => ['required', 'integer', function (string $atributo, mixed $valor, Closure $falla) use ($cambio, $inscripcion): void {
                if (! $cambio->carreraValida($inscripcion, (int) $this->input('sede'), (int) $valor)) {
                    $falla('La carrera no está habilitada en esa sede para el proceso.');
                }
            }],
            'turno' => ['required', 'integer', function (string $atributo, mixed $valor, Closure $falla) use ($cambio, $inscripcion): void {
                if (! $cambio->turnoValido($inscripcion, (int) $valor)) {
                    $falla('El turno no tiene aulas habilitadas en el proceso.');
                }
            }],
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sede.required' => 'La sede es obligatoria.',
            'carrera.required' => 'La carrera es obligatoria.',
            'turno.required' => 'El turno es obligatorio.',
            'motivo.required' => 'El motivo del cambio es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede exceder 500 caracteres.',
        ];
    }

    public function inscripcion(): Inscripcion
    {
        if ($this->registro === null) {
            $id = decode((string) $this->route('inscripcion'));
            abort_if($id === null, 404);

            $this->registro = Inscripcion::query()->with(['sede', 'carrera', 'turno'])->findOrFail($id);
        }

        return $this->registro;
    }
}
