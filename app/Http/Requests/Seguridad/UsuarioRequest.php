<?php

namespace App\Http\Requests\Seguridad;

use App\Http\Requests\CatalogoRequest;
use App\Models\Usuario;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuarioRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['nombres'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('correo')) {
            $this->merge(['correo' => Str::lower(trim((string) $this->input('correo')))]);
        }
    }

    /**
     * La contraseña es obligatoria al registrar; al editar, vacía significa que no cambia.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Usuario|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'correo' => ['required', 'email', 'max:150', Rule::unique('tbl_usuario', 'correo_usu')->ignore($usuario?->id_usu, 'id_usu')],
            'nombres' => ['required', 'string', 'max:150'],
            'password' => $usuario === null ? ['required', 'string', 'min:8'] : ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:tbl_rol,id_rol'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingrese un correo válido.',
            'correo.unique' => 'Ya existe un usuario con ese correo.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}
