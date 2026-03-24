<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoInscripcion: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Activo = 'ACTIVO';
    case Anulado = 'ANULADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Activo => 'lime',
            self::Anulado => 'rose',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Activo => 'check-squared',
            self::Anulado => 'cross-square',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
