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
            self::Habilitado => 'lime',
            self::Deshabilitado => 'rose',
            self::Cerrado => 'slate',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Habilitado => 'check-squared',
            self::Deshabilitado => 'cross-square',
            self::Cerrado => 'lock',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
