<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum TipoLenguaMaternaPersona: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Castellano = 'CASTELLANO';
    case Nativa = 'NATIVA';
    case Extranjera = 'EXTRANJERA';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Castellano => 'zinc',
            self::Nativa => 'emerald',
            self::Extranjera => 'sky',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Castellano => 'message',
            self::Nativa => 'translate',
            self::Extranjera => 'globe',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
