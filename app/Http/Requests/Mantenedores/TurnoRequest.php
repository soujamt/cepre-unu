<?php

namespace App\Http\Requests\Mantenedores;

use App\Models\Turno;
use Illuminate\Validation\Rule;

class TurnoRequest extends CatalogoRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Turno|null $turno */
        $turno = $this->route('turno');

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('tbl_turno', 'codigo_tur')->ignore($turno?->id_tur, 'id_tur')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_turno', 'nombre_tur')->ignore($turno?->id_tur, 'id_tur')],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio', 'after:hora_inicio'],
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
            'codigo.unique' => 'Ya existe un turno con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe un turno con ese nombre.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la de inicio.',
            'hora_inicio.required_with' => 'Indique la hora de inicio.',
            'hora_fin.required_with' => 'Indique la hora de fin.',
        ];
    }
}
