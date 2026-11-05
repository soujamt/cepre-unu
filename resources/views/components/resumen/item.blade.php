@props ([
    'label',
    'value',
    'icon' => null,
    'detalle' => null,
    'detalleIcon' => null,
    'tono' => 'neutral',
    'total' => null,
    'destacado' => false,
])

@php
    $detalleClasses = match ($tono) {
        'success' => 'bg-green-50 text-green-700 ring-green-600/15 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-400/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/15 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-400/20',
        'info' => 'bg-index-50 text-index-600 ring-index-500/15 dark:bg-index-500/10 dark:text-index-300 dark:ring-index-400/20',
        default => 'bg-zinc-100 text-zinc-600 ring-zinc-500/10 dark:bg-white/5 dark:text-zinc-400 dark:ring-white/10',
    };

    $iconClasses = match ($tono) {
        'success' => 'text-green-500',
        'warning' => 'text-amber-500',
        'danger' => 'text-red-500',
        'info' => 'text-index-500',
        default => 'text-zinc-400 dark:text-zinc-500',
    };
@endphp

<div
    {{
        $attributes->class([
            'flex min-w-0 flex-col justify-between gap-3 rounded-[0.55rem] border p-4 transition-shadow',
            'border-index-200 bg-index-50/70 shadow-card dark:border-index-500/25 dark:bg-index-500/10' => $destacado,
            'border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none' => ! $destacado,
        ])
    }}
>
    <div class="flex items-start justify-between gap-3">
        <p
            @class ([
                'text-sm font-medium',
                'text-index-600 dark:text-index-300' => $destacado,
                'text-zinc-600 dark:text-zinc-400' => ! $destacado,
            ])
        >
            {{ $label }}
        </p>

        @if ($icon)
            <x-ui.icon
                :name="$icon"
                size="5"
                :class="$destacado ? 'text-index-500 dark:text-index-300' : $iconClasses"
            />
        @endif
    </div>

    <p
        @class ([
            'text-3xl leading-none font-semibold tracking-tight tabular-nums',
            'text-index-600 dark:text-index-300' => $destacado,
            'text-zinc-900 dark:text-white' => ! $destacado,
        ])
    >
        {{ $value }}
        @if (! is_null($total))
            <span class="font-normal text-zinc-300 dark:text-zinc-600">/ {{ $total }}</span>
        @endif
    </p>

    @if (filled($detalle) || isset($pie))
        <div class="flex min-h-5 flex-wrap items-center gap-1.5">
            @if (filled($detalle))
                <span
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $detalleClasses }}"
                >
                    @if ($detalleIcon)
                        <x-ui.icon :name="$detalleIcon" size="3.5" />
                    @endif
                    {{ $detalle }}
                </span>
            @endif
            {{ $pie ?? '' }}
        </div>
    @endif
</div>
