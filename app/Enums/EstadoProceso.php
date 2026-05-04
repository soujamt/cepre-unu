<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoProceso: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Habilitado = 'HABILITADO';
    case Deshabilitado = 'DESHABILITADO';
    case Cerrado = 'CERRADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Habilitado => 'green',
            self::Deshabilitado => 'red',
            self::Cerrado => 'slate',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Habilitado => 'check-square',
            self::Deshabilitado => 'x-square',
            self::Cerrado => 'lock',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
