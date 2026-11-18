<?php

namespace App\Enums;

use App\Enums\Concerns\HasPresentableOptions;
use App\Enums\Contracts\PresentableEnum;

/**
 * A quién aplica una modalidad según su situación escolar al inscribirse.
 */
enum CondicionEgresoModalidad: string implements PresentableEnum
{
    use HasPresentableOptions;

    case Egresado = 'EGRESADO';
    case EnColegio = 'EN_COLEGIO';

    public function getNombre(): string
    {
        return match ($this) {
            self::Egresado => 'EGRESADOS',
            self::EnColegio => 'ESTUDIANTES EN COLEGIO',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Egresado => 'sky',
            self::EnColegio => 'amber',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Egresado => 'school',
            self::EnColegio => 'book-open',
        };
    }

    public function getIcono(): string
    {
        return $this->getIcon();
    }
}
