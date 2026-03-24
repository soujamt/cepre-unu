<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoRevisionExpediente: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Aprobado = 'APROBADO';
    case Observado = 'OBSERVADO';
    case Rechazado = 'RECHAZADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Aprobado => 'lime',
            self::Observado => 'sky',
            self::Rechazado => 'rose',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Aprobado => 'check-squared',
            self::Observado => 'info-square',
            self::Rechazado => 'cross-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
