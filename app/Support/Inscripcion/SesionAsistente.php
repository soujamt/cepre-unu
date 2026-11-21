<?php

namespace App\Support\Inscripcion;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * Guarda el estado de cada asistente abierto en la sesión del usuario, con una clave
 * aleatoria por pestaña. Se conservan los últimos asistentes abiertos de cada modo.
 */
class SesionAsistente
{
    private const MAXIMO_ABIERTOS = 5;

    public function __construct(private Session $sesion) {}

    public function abrir(string $modo, AsistenteInscripcion $asistente): string
    {
        $token = Str::random(32);
        $abiertos = [...array_diff($this->sesion->get("asistentes.{$modo}._orden", []), [$token]), $token];

        foreach (array_slice($abiertos, 0, -self::MAXIMO_ABIERTOS) as $antiguo) {
            $this->sesion->forget("asistentes.{$modo}.{$antiguo}");
        }

        $this->sesion->put("asistentes.{$modo}._orden", array_slice($abiertos, -self::MAXIMO_ABIERTOS));
        $this->guardar($modo, $token, $asistente);

        return $token;
    }

    /**
     * @template T of AsistenteInscripcion
     *
     * @param  class-string<T>  $clase
     * @return T|null
     */
    public function cargar(string $modo, string $token, string $clase): ?AsistenteInscripcion
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            return null;
        }

        $estado = $this->sesion->get("asistentes.{$modo}.{$token}");

        return is_array($estado) ? $clase::desdeEstado($estado) : null;
    }

    public function guardar(string $modo, string $token, AsistenteInscripcion $asistente): void
    {
        $this->sesion->put("asistentes.{$modo}.{$token}", $asistente->estado());
    }

    public function cerrar(string $modo, string $token): void
    {
        $this->sesion->forget("asistentes.{$modo}.{$token}");
        $this->sesion->put("asistentes.{$modo}._orden", array_values(array_diff($this->sesion->get("asistentes.{$modo}._orden", []), [$token])));
    }
}
