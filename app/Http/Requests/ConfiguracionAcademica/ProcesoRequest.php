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
     * Código interno del proceso a partir del nombre: "2026-II" → "2026_2". Usa el
     * número del periodo para que coincida con el de los procesos ya registrados.
     */
    public static function codigoDesdeNombre(string $nombre): string
    {
        $proceso = new Proceso(['nombre_pro' => $nombre]);

        return $proceso->numeroPeriodo() !== null
            ? $proceso->anioPeriodo().'_'.$proceso->numeroPeriodo()
            : str_replace('-', '_', (string) str($nombre)->slug('_'));
    }

    /**
     * Además de pasar el nombre a mayúsculas, acepta el periodo escrito con número
     * ("2026-2") y lo convierte a romanos ("2026-II").
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($this->input('nombre')) && preg_match('/^(\d{4})\s*-\s*([1-3])$/', $this->input('nombre'), $partes)) {
            $this->merge(['nombre' => Proceso::nombreDePeriodo((int) $partes[1], (int) $partes[2])]);
        }
    }

    /**
     * El nombre sigue el formato AAAA-I, AAAA-II o AAAA-III y ni el nombre ni el código que genera pueden
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
            'anio_extraordinario_desde' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'El nombre debe tener el año y el periodo en romanos: 2026-I, 2026-II o 2026-III.',
            'fecha_inicio.required' => 'La fecha y hora de apertura es obligatoria.',
            'fecha_inicio.date' => 'Ingrese una fecha y hora de apertura válida.',
            'fecha_fin.required' => 'La fecha y hora de cierre es obligatoria.',
            'fecha_fin.date' => 'Ingrese una fecha y hora de cierre válida.',
            'fecha_fin.after' => 'El cierre debe ser posterior a la apertura.',
            'anio_extraordinario_desde.integer' => 'Ingrese un año de cuatro dígitos.',
            'anio_extraordinario_desde.digits' => 'Ingrese un año de cuatro dígitos.',
            'anio_extraordinario_desde.min' => 'Ingrese un año desde 1950.',
            'anio_extraordinario_desde.max' => 'Ingrese un año hasta 2100.',
        ];
    }
}
