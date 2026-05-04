<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoObligacionPago: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Parcial = 'PARCIAL';
    case Pagado = 'PAGADO';
    case Exonerado = 'EXONERADO';
    case Anulado = 'ANULADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Parcial => 'sky',
            self::Pagado => 'green',
            self::Exonerado => 'violet',
            self::Anulado => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Parcial => 'wallet',
            self::Pagado => 'check-square',
            self::Exonerado => 'ticket',
            self::Anulado => 'x-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
