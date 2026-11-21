<?php

namespace App\Http\Requests\Pagos;

use App\Models\ImportacionBancoDetalle;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class PagoManualRequest extends FormRequest
{
    /**
     * Cuentas de la UNU donde se registran pagos a mano.
     */
    public const UBICACIONES = ['ADMISIÓN', 'POSGRADO', 'PARENTESCO UNU', 'OTRA CUENTA UNU'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Si el pago ya no se puede corregir, solo se valida la observación.
     */
    public function soloObservacion(): bool
    {
        /** @var ImportacionBancoDetalle|null $pago */
        $pago = $this->route('pago');

        return $pago !== null && ! $pago->esEditableAMano();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => trim((string) $this->input('dni')),
            'numero_operacion' => mb_strtoupper(trim((string) $this->input('numero_operacion')), 'UTF-8'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        if ($this->soloObservacion()) {
            return ['observacion' => ['nullable', 'string', 'max:255']];
        }

        /** @var ImportacionBancoDetalle|null $pago */
        $pago = $this->route('pago');

        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'dni' => ['required', 'regex:/^\d{8,12}$/'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'importe' => ['required', 'numeric', 'gte:0'],
            'numero_operacion' => [
                'required',
                'regex:/^\d{1,30}$/',
                function (string $attribute, mixed $value, Closure $fail) use ($pago): void {
                    $repetido = ImportacionBancoDetalle::query()
                        ->where('numero_documento_ibd', $this->input('dni'))
                        ->where('numero_operacion_visible_ibd', $value)
                        ->when($pago, fn ($query) => $query->whereKeyNot($pago->id_ibd))
                        ->exists();

                    if ($repetido) {
                        $fail('Ya existe un pago con ese número de operación para el mismo DNI.');
                    }
                },
            ],
            'ubicacion' => ['required', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.regex' => 'El DNI debe tener entre 8 y 12 dígitos.',
            'importe.required' => 'El importe es obligatorio.',
            'importe.numeric' => 'Ingrese un importe válido.',
            'importe.gte' => 'El importe no puede ser negativo.',
            'numero_operacion.required' => 'El número de operación es obligatorio.',
            'numero_operacion.regex' => 'El número de operación solo admite dígitos.',
            'ubicacion.required' => 'Indique dónde se realizó el pago.',
            'observacion.max' => 'La observación no debe superar los 255 caracteres.',
        ];
    }
}
