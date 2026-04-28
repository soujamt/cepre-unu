<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum LugarAula: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Unu = 'UNU';
    case Uap = 'UAP';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unu => 'sky',
            self::Uap => 'rose',
        };
    }

    public function getIcon(): string
    {
        return 'map-pin';
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
