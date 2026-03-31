<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoValidacionPago: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Verificado = 'VERIFICADO';
    case Rechazado = 'RECHAZADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Verificado => 'green',
            self::Rechazado => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Verificado => 'check-square',
            self::Rechazado => 'x-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
