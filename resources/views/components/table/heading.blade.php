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
    {{ $attributes->class(["h-10 px-3 first:ps-4 last:pe-4 {$alignmentClasses} text-[11px] font-medium uppercase tracking-[0.08em] text-zinc-500 dark:text-zinc-400"]) }}
>
    {{ $slot }}
</th>
