<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoImportacionBancoDetalle: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Asociado = 'ASOCIADO';
    case Observado = 'OBSERVADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Asociado => 'green',
            self::Observado => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Asociado => 'link',
            self::Observado => 'alert-triangle',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
