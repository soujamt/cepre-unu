@props (['name', 'variant' => 'outline'])

@php
    $sizeClasses = match ($variant) {
        'mini' => '[:where(&)]:size-5',
        'micro' => '[:where(&)]:size-4',
        default => '[:where(&)]:size-6',
    };
@endphp

<span
    {{ $attributes->class(['ui-boxicon inline-flex shrink-0 items-center justify-center', $sizeClasses]) }}
    data-flux-icon
    data-slot="icon"
    aria-hidden="true"
><i class="bxf bx-{{ $name }}"></i></span>
