<?php

namespace App\Http\Requests\Mantenedores;

use App\Http\Requests\CatalogoRequest;
use App\Models\Colegio;
use Illuminate\Validation\Rule;

class ColegioRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['nombre'];
    }

    /**
     * @return list<string>
     */
    protected function camposDeTexto(): array
    {
        return ['id', 'codigo_modular', 'direccion'];
    }

    /**
     * El código del colegio es su llave: se indica al registrarlo y luego no cambia.
     * El código, el código modular y el nombre son únicos también frente a colegios eliminados.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Colegio|null $colegio */
        $colegio = $this->route('colegio');

        $reglas = [
            'codigo_modular' => ['nullable', 'string', 'max:255', Rule::unique('tbl_colegio', 'codigo_modular_col')->ignore($colegio?->id_col, 'id_col')],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_colegio', 'nombre_col')->ignore($colegio?->id_col, 'id_col')],
            'id_tipo_colegio' => ['nullable', 'integer', 'exists:tbl_tipo_colegio,id_tco'],
            'id_ubigeo' => ['nullable', 'string', 'size:6', 'exists:tbl_ubigeo,id_ubi'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];

        if ($colegio === null) {
            $reglas['id'] = ['required', 'string', 'max:10', Rule::unique('tbl_colegio', 'id_col')];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.required' => 'El código del colegio es obligatorio.',
            'id.max' => 'El código no debe superar los 10 caracteres.',
            'id.unique' => 'Ya existe un colegio con ese código.',
            'codigo_modular.unique' => 'Ya existe un colegio con ese código modular.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe un colegio con ese nombre.',
            'id_tipo_colegio.integer' => 'Seleccione un tipo de colegio válido.',
            'id_tipo_colegio.exists' => 'Seleccione un tipo de colegio válido.',
            'id_ubigeo.size' => 'Seleccione un ubigeo válido.',
            'id_ubigeo.exists' => 'Seleccione un ubigeo válido.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
