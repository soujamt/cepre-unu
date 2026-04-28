<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

enum PisoAula: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Piso1 = 'PISO 1';
    case Piso2 = 'PISO 2';
    case Piso3 = 'PISO 3';
    case Piso4 = 'PISO 4';
    case Piso5 = 'PISO 5';

    public function getNombre(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return 'zinc';
    }

    public function getIcon(): string
    {
        return 'building-office';
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
