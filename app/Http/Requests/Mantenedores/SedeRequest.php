<?php

namespace App\Http\Requests\Mantenedores;

use App\Models\Sede;
use Illuminate\Validation\Rule;

class SedeRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['codigo', 'nombre', 'direccion'];
    }

    /**
     * El código y el nombre son únicos también frente a sedes eliminadas.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Sede|null $sede */
        $sede = $this->route('sede');

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('tbl_sede', 'codigo_sed')->ignore($sede?->id_sed, 'id_sed')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_sede', 'nombre_sed')->ignore($sede?->id_sed, 'id_sed')],
            'id_ubigeo' => ['required', 'string', 'size:6', 'exists:tbl_ubigeo,id_ubi'],
            'direccion' => ['nullable', 'string', 'max:255'],
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
            'codigo.unique' => 'Ya existe una sede con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe una sede con ese nombre.',
            'id_ubigeo.required' => 'El ubigeo es obligatorio.',
            'id_ubigeo.size' => 'Seleccione un ubigeo válido.',
            'id_ubigeo.exists' => 'Seleccione un ubigeo válido.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
