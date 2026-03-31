<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoUsuario: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Habilitado = 'HABILITADO';
    case Deshabilitado = 'DESHABILITADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Habilitado => 'green',
            self::Deshabilitado => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Habilitado => 'check-square',
            self::Deshabilitado => 'x-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
