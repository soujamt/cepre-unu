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

<th
    {{ $attributes->class(["h-11 px-3 {$alignmentClasses} text-xs font-medium uppercase tracking-[0.12em] text-zinc-600 dark:text-zinc-200"]) }}
>
    {{ $slot }}
</th>
