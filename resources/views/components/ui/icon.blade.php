@props (['name', 'size' => '5', 'weight' => null, 'variant' => null, 'class' => ''])

@php
    $resolvedSize = is_numeric($size) ? ((float) $size / 4) . 'rem' : $size;
@endphp

<span
    {{ $attributes->merge([
        'class' => trim("ui-icon inline-flex shrink-0 items-center justify-center leading-none {$class}"),
        'style' => "font-size: {$resolvedSize};",
        'aria-hidden' => 'true',
    ]) }}
>
    <i class="ui-icon-glyph bxf bx-{{ $name }}"></i>
</span>
