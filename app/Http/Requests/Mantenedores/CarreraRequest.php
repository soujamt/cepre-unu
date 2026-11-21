<?php

namespace App\Http\Requests\Mantenedores;

use App\Http\Requests\CatalogoRequest;
use App\Models\Carrera;
use Illuminate\Validation\Rule;

class CarreraRequest extends CatalogoRequest
{
    /**
     * El código y el nombre son únicos también frente a carreras eliminadas,
     * porque la tabla mantiene los índices únicos sobre ellas.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Carrera|null $carrera */
        $carrera = $this->route('carrera');

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('tbl_carrera', 'codigo_car')->ignore($carrera?->id_car, 'id_car')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_carrera', 'nombre_car')->ignore($carrera?->id_car, 'id_car')],
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
            'codigo.unique' => 'Ya existe una carrera con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una carrera con ese nombre.',
        ];
    }
}
