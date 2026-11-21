<?php

namespace App\Http\Requests\Seguridad;

use App\Http\Requests\CatalogoRequest;
use App\Models\Permiso;
use Closure;
use Illuminate\Support\Str;

class PermisoRequest extends CatalogoRequest
{
    /**
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['nombre'];
    }

    /**
     * Clave del permiso a partir del nombre: "Reglas de pago" → "reglas_de_pago".
     */
    public static function claveDesdeNombre(string $nombre): string
    {
        return Str::replace('-', '_', Str::slug($nombre));
    }

    /**
     * El nombre no puede generar la misma clave que otro permiso.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Permiso|null $permiso */
        $permiso = $this->route('permiso');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($permiso): void {
                    $existe = Permiso::query()
                        ->withTrashed()
                        ->where('clave_permiso', self::claveDesdeNombre((string) $value))
                        ->when($permiso, fn ($query) => $query->whereKeyNot($permiso->id_permiso))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un permiso con ese nombre.');
                    }
                },
            ],
            'acciones' => ['required', 'array', 'min:1'],
            'acciones.*' => ['integer', 'exists:tbl_accion,id_acc'],
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
            'acciones.required' => 'Debe seleccionar al menos una acción.',
            'acciones.min' => 'Debe seleccionar al menos una acción.',
        ];
    }
}
