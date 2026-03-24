<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum TipoBeneficioConvenio: string implements PresentableEnum
{
    use HasPresentableOptions;

    case ExoneracionTotal = 'EXONERACION_TOTAL';
    case ExoneracionParcial = 'EXONERACION_PARCIAL';
    case Descuento = 'DESCUENTO';

    public function getNombre(): string
    {
        return match ($this) {
            self::ExoneracionTotal => 'EXONERACION TOTAL',
            self::ExoneracionParcial => 'EXONERACION PARCIAL',
            self::Descuento => 'DESCUENTO',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ExoneracionTotal => 'violet',
            self::ExoneracionParcial => 'sky',
            self::Descuento => 'lime',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ExoneracionTotal => 'badge-percent',
            self::ExoneracionParcial => 'ticket-percent',
            self::Descuento => 'receipt-percent',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
