<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los formularios de los mantenedores: quita espacios repetidos y pasa a
 * mayúsculas los campos que el sistema guarda en mayúsculas (código, nombre…).
 * El permiso de crear o editar lo controla el middleware de la ruta.
 */
abstract class CatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Campos que se guardan en mayúsculas.
     *
     * @return list<string>
     */
    protected function camposEnMayusculas(): array
    {
        return ['codigo', 'nombre'];
    }

    /**
     * Campos de texto libre que solo se recortan (por ejemplo, la descripción).
     *
     * @return list<string>
     */
    protected function camposDeTexto(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $normalizados = [];

        foreach ($this->camposEnMayusculas() as $campo) {
            if ($this->has($campo)) {
                $texto = $this->textoNormalizado($campo);
                $normalizados[$campo] = $texto === null ? null : mb_strtoupper($texto, 'UTF-8');
            }
        }

        foreach ($this->camposDeTexto() as $campo) {
            if ($this->has($campo)) {
                $normalizados[$campo] = $this->textoNormalizado($campo);
            }
        }

        $this->merge($normalizados);
    }

    private function textoNormalizado(string $campo): ?string
    {
        $texto = preg_replace('/\s+/u', ' ', trim((string) $this->input($campo)));

        return filled($texto) ? $texto : null;
    }
}
