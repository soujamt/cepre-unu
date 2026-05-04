@props (['name', 'size' => '5', 'weight' => 'regular', 'variant' => null, 'class' => ''])

@php
    $weight = match ($variant) {
        'solid' => 'fill',
        default => $weight,
    };

    $resolvedWeight = match ($weight) {
        'thin' => 'thin',
        'regular' => 'regular',
        'fill' => 'fill',
        'bold' => 'bold',
        'duotone' => 'duotone',
        default => 'regular',
    };

    $weightClass = match ($resolvedWeight) {
        'thin' => 'ph-thin',
        'regular' => 'ph',
        'fill' => 'ph-fill',
        'bold' => 'ph-bold',
        'duotone' => 'ph-duotone',
        default => 'ph',
    };

    $resolvedSize = is_numeric($size) ? ((float) $size / 4) . 'rem' : $size;
@endphp

<span
    {{ $attributes->merge([
        'class' => trim("ui-icon inline-flex shrink-0 items-center justify-center leading-none {$class}"),
        'style' => "font-size: {$resolvedSize};",
        'aria-hidden' => 'true',
    ]) }}
>
    <i class="ui-icon-glyph {{ $weightClass }} ph-{{ \Illuminate\Support\Str::kebab($name) }}"></i>
</span>
