<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum SexoPersona: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Masculino = 'MASCULINO';
    case Femenino = 'FEMENINO';
    case Otro = 'OTRO';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Masculino => 'sky',
            self::Femenino => 'pink',
            self::Otro => 'violet',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Masculino => 'user',
            self::Femenino => 'user',
            self::Otro => 'users',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
