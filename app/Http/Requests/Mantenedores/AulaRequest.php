<?php

namespace App\Http\Requests\Mantenedores;

use App\Enums\LugarAula;
use App\Enums\PisoAula;
use App\Models\Aula;
use Illuminate\Validation\Rule;

class AulaRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['nombre'];
    }

    /**
     * El nombre es único también frente a aulas eliminadas.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Aula|null $aula */
        $aula = $this->route('aula');

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tbl_aula', 'nombre_aul')->ignore($aula?->id_aul, 'id_aul')],
            'piso' => ['required', Rule::enum(PisoAula::class)],
            'lugar' => ['required', Rule::enum(LugarAula::class)],
            'orden' => ['required', 'integer', 'min:1'],
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
            'nombre.unique' => 'Ya existe un aula con ese nombre.',
            'piso.required' => 'El piso es obligatorio.',
            'piso.enum' => 'Seleccione un piso válido.',
            'lugar.required' => 'El lugar es obligatorio.',
            'lugar.enum' => 'Seleccione un lugar válido.',
            'orden.required' => 'El orden es obligatorio.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser mayor o igual a 1.',
        ];
    }
}
