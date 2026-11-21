<?php

namespace App\Http\Requests\Seguridad;

use App\Http\Requests\CatalogoRequest;
use App\Models\Rol;
use Illuminate\Validation\Rule;

class RolRequest extends CatalogoRequest
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
        return ['descripcion'];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Rol|null $rol */
        $rol = $this->route('rol');

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_rol', 'nombre_rol')->ignore($rol?->id_rol, 'id_rol')],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
            'descripcion.max' => 'La descripción no debe superar los 500 caracteres.',
        ];
    }
}
