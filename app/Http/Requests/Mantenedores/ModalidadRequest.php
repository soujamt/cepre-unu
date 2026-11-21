<?php

namespace App\Http\Requests\Mantenedores;

use App\Enums\CondicionEgresoModalidad;
use App\Models\ModalidadAdmision;
use Illuminate\Validation\Rule;

class ModalidadRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposDeTexto(): array
    {
        return ['descripcion'];
    }

    /**
     * El código y el nombre son únicos también frente a modalidades eliminadas.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var ModalidadAdmision|null $modalidad */
        $modalidad = $this->route('modalidad');

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('tbl_modalidad_admision', 'codigo_mad')->ignore($modalidad?->id_mad, 'id_mad')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_modalidad_admision', 'nombre_mad')->ignore($modalidad?->id_mad, 'id_mad')],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'condicion_egreso' => ['required', Rule::enum(CondicionEgresoModalidad::class)],
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
            'codigo.unique' => 'Ya existe una modalidad con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una modalidad con ese nombre.',
            'descripcion.max' => 'La descripción no debe superar los 1000 caracteres.',
            'condicion_egreso.required' => 'Indique a quién aplica la modalidad.',
            'condicion_egreso.enum' => 'Seleccione a quién aplica la modalidad.',
        ];
    }
}
