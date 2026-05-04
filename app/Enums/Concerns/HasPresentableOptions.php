<?php

namespace App\Enums\Concerns;

trait HasPresentableOptions
{
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getNombre();
        }

        return $options;
    }

    public static function labels(): array
    {
        return array_map(
            static fn (self $case): array => $case->toArray(),
            self::cases(),
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'nombre' => $this->getNombre(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'icono' => $this->getIcono(),
        ];
    }

    public function toBadge(): array
    {
        return [
            'label' => $this->getNombre(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
        ];
    }
}
