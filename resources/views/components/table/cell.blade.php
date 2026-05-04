@props([
    'align' => 'start',
])

@php
    $alignmentClasses = match ($align) {
        'center' => 'text-center',
        'end' => 'text-end',
        default => 'text-start',
    };
@endphp

<td {{ $attributes->class(["h-11 px-3 {$alignmentClasses} text-sm font-normal whitespace-nowrap text-zinc-700 dark:text-zinc-200"]) }}>
    {{ $slot }}
</td>
