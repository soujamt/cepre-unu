@props (['columnas' => 4])

@php
    $gridClasses = match ((int) $columnas) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-3',
        5 => 'sm:grid-cols-2 lg:grid-cols-5',
        default => 'sm:grid-cols-2 xl:grid-cols-4',
    };
@endphp

<div
    {{ $attributes->class(['rounded-xl border border-zinc-200/80 bg-zinc-100/70 p-1 dark:border-white/[0.06] dark:bg-zinc-950/60']) }}
>
    <div class="grid grid-cols-1 gap-1 {{ $gridClasses }}">
        {{ $slot }}
    </div>
</div>
