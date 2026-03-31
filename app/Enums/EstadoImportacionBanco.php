<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum EstadoImportacionBanco: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Pendiente = 'PENDIENTE';
    case Procesado = 'PROCESADO';
    case Observado = 'OBSERVADO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'amber',
            self::Procesado => 'green',
            self::Observado => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'clock',
            self::Procesado => 'check-square',
            self::Observado => 'alert-triangle',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
