<?php

namespace App\Http\Requests\ConfiguracionAcademica;

use App\Models\ConceptoPago;
use App\Models\Proceso;
use App\Models\ReglaPago;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReglaPagoRequest extends FormRequest
{
    /**
     * Documentos que puede pedir una modalidad de pago (beca o parentesco).
     */
    public const DOCUMENTOS_REQUERIDOS = ['N° DE CONSTANCIA', 'N° DE RESOLUCIÓN'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sin cuotas, el máximo es 1 y no hay monto mínimo; exigir pago completo y
     * permitir cuotas se excluyen entre sí.
     */
    protected function prepareForValidation(): void
    {
        $permiteCuotas = $this->boolean('permite_cuotas') && ! $this->boolean('requiere_pago_completo');

        $this->merge([
            'permite_cuotas' => $permiteCuotas,
            'requiere_pago_completo' => $this->boolean('requiere_pago_completo'),
            'numero_maximo_cuotas' => $permiteCuotas ? $this->input('numero_maximo_cuotas') : 1,
        ]);
    }

    /**
     * Una sola regla por concepto, proceso y modalidad (o todas las modalidades), y la
     * vigencia dentro de las fechas del proceso.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var ReglaPago|null $regla */
        $regla = $this->route('regla');
        $permiteCuotas = $this->boolean('permite_cuotas');

        return [
            'id_concepto_pago' => ['required', 'integer', 'exists:tbl_concepto_pago,id_cpg'],
            'id_proceso' => [
                'required',
                'integer',
                'exists:tbl_proceso,id_pro',
                function (string $attribute, mixed $value, Closure $fail) use ($regla): void {
                    if (blank($this->input('id_concepto_pago'))) {
                        return;
                    }

                    $existe = ReglaPago::query()
                        ->withTrashed()
                        ->where('id_concepto_pago', (int) $this->input('id_concepto_pago'))
                        ->where('id_proceso', (int) $value)
                        ->when(
                            filled($this->input('id_modalidad_admision')),
                            fn ($query) => $query->where('id_modalidad_admision', (int) $this->input('id_modalidad_admision')),
                            fn ($query) => $query->whereNull('id_modalidad_admision'),
                        )
                        ->when($regla, fn ($query) => $query->whereKeyNot($regla->id_rpa))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe una regla de pago con ese mismo contexto.');
                    }
                },
            ],
            'id_modalidad_admision' => ['nullable', 'integer', 'exists:tbl_modalidad_admision,id_mad'],
            'monto_total' => ['required', 'numeric', 'gte:0'],
            'permite_cuotas' => [
                'boolean',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $concepto = $value ? ConceptoPago::query()->find((int) $this->input('id_concepto_pago')) : null;

                    if ($concepto !== null && ! $concepto->permite_cuotas_cpg) {
                        $fail('El concepto seleccionado no permite cuotas.');
                    }
                },
            ],
            'monto_minimo_primera_cuota' => [
                Rule::excludeIf(! $permiteCuotas),
                'required',
                'numeric',
                'gt:0',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((float) $value > (float) ($this->input('monto_total') ?: 0)) {
                        $fail('El monto mínimo de la primera cuota no puede ser mayor al monto total.');
                    }
                },
            ],
            'numero_maximo_cuotas' => [Rule::excludeIf(! $permiteCuotas), 'required', 'integer', 'min:2'],
            'requiere_pago_completo' => ['boolean'],
            'documento_requerido' => ['nullable', Rule::in(self::DOCUMENTOS_REQUERIDOS)],
            'vigente_desde' => ['required', 'date', $this->dentroDelProceso('inicial')],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde', $this->dentroDelProceso('final')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_concepto_pago.required' => 'El concepto de pago es obligatorio.',
            'id_proceso.required' => 'El proceso es obligatorio.',
            'id_modalidad_admision.exists' => 'La modalidad seleccionada no es válida.',
            'monto_total.required' => 'El monto total es obligatorio.',
            'monto_total.numeric' => 'Ingrese un monto válido.',
            'monto_total.gte' => 'El monto total no puede ser negativo.',
            'monto_minimo_primera_cuota.required' => 'El monto mínimo de la primera cuota es obligatorio cuando se permiten cuotas.',
            'monto_minimo_primera_cuota.gt' => 'El monto mínimo de la primera cuota debe ser mayor a 0.',
            'numero_maximo_cuotas.required' => 'El número máximo de cuotas es obligatorio.',
            'numero_maximo_cuotas.min' => 'Debe indicar al menos 2 cuotas cuando la regla permite cuotas.',
            'documento_requerido.in' => 'Seleccione un documento válido.',
            'vigente_desde.required' => 'La vigencia desde es obligatoria.',
            'vigente_hasta.after_or_equal' => 'La vigencia final no puede ser menor que la inicial.',
        ];
    }

    private function dentroDelProceso(string $extremo): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($extremo): void {
            $proceso = filled($this->input('id_proceso')) ? Proceso::query()->find((int) $this->input('id_proceso')) : null;

            if ($proceso === null || blank($value)) {
                return;
            }

            $inicio = $proceso->fecha_inicio_pro?->format('Y-m-d');
            $fin = $proceso->fecha_fin_pro?->format('Y-m-d');
            $fecha = substr((string) $value, 0, 10);

            if ($inicio !== null && $fecha < $inicio) {
                $fail("La vigencia {$extremo} no puede ser menor que la fecha de inicio del proceso.");
            } elseif ($fin !== null && $fecha > $fin) {
                $fail("La vigencia {$extremo} no puede ser mayor que la fecha final del proceso.");
            }
        };
    }
}
