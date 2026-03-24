<?php

namespace App\Enums\Contracts;

interface PresentableEnum
{
    public static function values(): array;

    public static function options(): array;

    public static function labels(): array;

    public function getNombre(): string;

    public function getColor(): string;

    public function getIcon(): string;

    public function getIcono(): string;

    public function toArray(): array;

    public function toBadge(): array;
}
