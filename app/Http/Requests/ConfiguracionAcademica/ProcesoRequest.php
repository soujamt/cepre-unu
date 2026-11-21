<?php

namespace App\Http\Requests\ConfiguracionAcademica;

use App\Http\Requests\CatalogoRequest;
use App\Models\Proceso;
use Closure;

class ProcesoRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['nombre'];
    }

    /**
     * Código del proceso a partir del nombre: "2026-2" → "2026_2".
     */
    public static function codigoDesdeNombre(string $nombre): string
    {
        return str_replace('-', '_', (string) str($nombre)->slug('_'));
    }

    /**
     * El nombre sigue el formato AAAA-N y ni el nombre ni el código que genera pueden
     * repetirse, tampoco frente a procesos eliminados.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Proceso|null $proceso */
        $proceso = $this->route('proceso');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'regex:'.Proceso::FORMATO_NOMBRE,
                function (string $attribute, mixed $value, Closure $fail) use ($proceso): void {
                    $otros = fn () => Proceso::query()->withTrashed()->when($proceso, fn ($query) => $query->whereKeyNot($proceso->id_pro));

                    if ($otros()->where('nombre_pro', $value)->exists()) {
                        $fail('Ya existe un proceso con ese nombre.');
                    } elseif ($otros()->where('codigo_pro', self::codigoDesdeNombre((string) $value))->exists()) {
                        $fail('El nombre genera un código que ya existe en otro proceso.');
                    }
                },
            ],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'anio_egreso_max_ordinario' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'El nombre debe tener el formato AAAA-N, por ejemplo 2026-2.',
            'fecha_inicio.required' => 'La fecha y hora de apertura es obligatoria.',
            'fecha_inicio.date' => 'Ingrese una fecha y hora de apertura válida.',
            'fecha_fin.required' => 'La fecha y hora de cierre es obligatoria.',
            'fecha_fin.date' => 'Ingrese una fecha y hora de cierre válida.',
            'fecha_fin.after' => 'El cierre debe ser posterior a la apertura.',
            'anio_egreso_max_ordinario.integer' => 'Ingrese un año de cuatro dígitos.',
            'anio_egreso_max_ordinario.digits' => 'Ingrese un año de cuatro dígitos.',
            'anio_egreso_max_ordinario.min' => 'Ingrese un año desde 1950.',
            'anio_egreso_max_ordinario.max' => 'Ingrese un año hasta 2100.',
        ];
    }
}
