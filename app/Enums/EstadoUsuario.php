<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoUsuario: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Habilitado = 'HABILITADO';
    case Deshabilitado = 'DESHABILITADO';
    case Bloqueado = 'BLOQUEADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Habilitado => 'lime',
            self::Deshabilitado => 'rose',
            self::Bloqueado => 'amber',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Habilitado => 'check-squared',
            self::Deshabilitado => 'cross-square',
            self::Bloqueado => 'lock',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
