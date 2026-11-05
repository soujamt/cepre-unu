@props ([
    'paso',
    'total',
    'titulo',
    'descripcion' => null,
    'icono' => null,
])

<div {{ $attributes->class(['rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            @if ($icono)
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-index-50 text-index-600 ring-1 ring-index-500/15 ring-inset dark:bg-index-500/10 dark:text-index-300 dark:ring-index-400/20">
                    <x-ui.icon :name="$icono" size="5" />
                </span>
            @endif

            <div class="min-w-0 space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-index-600 uppercase dark:text-index-300">Paso {{ $paso }} de {{ $total }}</p>
                <h3 class="text-lg leading-6 font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h3>
                @if ($descripcion)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $descripcion }}</p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5" aria-hidden="true">
            @for ($indice = 1; $indice <= $total; $indice++)
                <span
                    @class ([
                        'h-1.5 rounded-full transition-all',
                        'w-8 bg-index-500' => $indice === $paso,
                        'w-4 bg-green-500' => $indice < $paso,
                        'w-4 bg-zinc-200 dark:bg-white/10' => $indice > $paso,
                    ])
                ></span>
            @endfor
        </div>
    </div>
</div>
