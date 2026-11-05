@props ([
    'activos' => 0,
    'limpiar' => null,
    'titulo' => 'Filtros adicionales',
    'columnas' => 2,
    'align' => 'end',
])

@php
    $gridClasses = match ((int) $columnas) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2',
    };

    $panelWidth = match ((int) $columnas) {
        1 => 'sm:w-80',
        3 => 'sm:w-[44rem]',
        default => 'sm:w-[34rem]',
    };

    $anchorPlacement = $align === 'start' ? 'bottom-start' : 'bottom-end';
@endphp

<div
    x-data="{ abierto: false }"
    x-on:keydown.escape.window="abierto = false"
    x-on:click.outside="abierto = false"
    {{ $attributes->class(['relative w-full sm:w-auto']) }}
>
    <flux:button
        class="w-full font-normal sm:w-auto"
        x-ref="disparador"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto"
    >
        <x-slot name="icon">
            <x-ui.icon name="slider-alt" size="5" />
        </x-slot>
        Más filtros
        @if ($activos > 0)
            <span
                class="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-index-500 px-1.5 text-[11px] leading-5 font-semibold text-white"
            >
                {{ $activos }}
            </span>
        @endif
    </flux:button>

    <div
        x-show="abierto"
        x-cloak
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="-translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-anchor.{{ $anchorPlacement }}.offset.8="$refs.disparador"
        class="z-30 w-[calc(100vw-2rem)] {{ $panelWidth }}"
    >
        <div
            class="rounded-xl border border-zinc-200/80 bg-white shadow-[0_16px_40px_-12px_rgba(0,0,0,0.18)] dark:border-white/10 dark:bg-zinc-900 dark:shadow-[0_16px_40px_-12px_rgba(0,0,0,0.6)]"
        >
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-white/[0.06]">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</p>
                <button
                    type="button"
                    class="-me-1 flex size-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-white/5 dark:hover:text-zinc-200"
                    x-on:click="abierto = false"
                    aria-label="Cerrar filtros"
                >
                    <x-ui.icon name="x" size="4" />
                </button>
            </div>

            <div class="grid gap-3 p-4 {{ $gridClasses }}">
                {{ $slot }}
            </div>

            @if ($limpiar)
                <div
                    class="flex items-center justify-between gap-3 border-t border-zinc-100 bg-zinc-50/60 px-4 py-2.5 dark:border-white/[0.06] dark:bg-white/[0.02]"
                >
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $activos > 0 ? $activos.' '.($activos === 1 ? 'filtro activo' : 'filtros activos') : 'Sin filtros adicionales' }}
                    </span>
                    <flux:button
                        size="sm"
                        variant="subtle"
                        wire:click="{{ $limpiar }}"
                        :disabled="$activos === 0"
                    >
                        <x-slot name="icon">
                            <x-ui.icon name="trash" size="4" />
                        </x-slot>
                        Limpiar
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
