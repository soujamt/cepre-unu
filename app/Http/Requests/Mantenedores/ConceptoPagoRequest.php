<?php

namespace App\Http\Requests\Mantenedores;

use App\Models\ConceptoPago;
use Closure;
use Illuminate\Validation\Rule;

class ConceptoPagoRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposDeTexto(): array
    {
        return ['descripcion'];
    }

    /**
     * El código y el nombre son únicos también frente a conceptos eliminados. Al editar,
     * no se puede quitar "Permite cuotas" si hay reglas de pago en cuotas con el concepto.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var ConceptoPago|null $concepto */
        $concepto = $this->route('concepto');

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('tbl_concepto_pago', 'codigo_cpg')->ignore($concepto?->id_cpg, 'id_cpg')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_concepto_pago', 'nombre_cpg')->ignore($concepto?->id_cpg, 'id_cpg')],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'permite_cuotas' => [
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($concepto): void {
                    if ($concepto === null || $this->boolean('permite_cuotas')) {
                        return;
                    }

                    if ($concepto->reglasPago()->where('permite_cuotas_rpa', true)->exists()) {
                        $fail('No se puede quitar porque hay reglas de pago en cuotas que usan este concepto.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no debe superar los 20 caracteres.',
            'codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'codigo.unique' => 'Ya existe un concepto de pago con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe un concepto de pago con ese nombre.',
            'descripcion.max' => 'La descripción no debe superar los 1000 caracteres.',
        ];
    }
}
