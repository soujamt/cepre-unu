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
            self::Pagado => 'lime',
            self::Exonerado => 'violet',
            self::Anulado => 'rose',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Parcial => 'wallet',
            self::Pagado => 'check-squared',
            self::Exonerado => 'ticket',
            self::Anulado => 'cross-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
