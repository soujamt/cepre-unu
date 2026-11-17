@props (['titulo', 'subtitulo' => null])

<header class="border-b border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4">
        <a href="{{ route('publico.portal') }}" wire:navigate class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <img src="{{ asset('assets/media/logo_unu.png') }}" alt="" class="w-6" />
            </span>
            <span class="leading-tight">
                <span class="block text-sm font-semibold text-zinc-900 dark:text-white">CEPRE UNU</span>
                <span class="block text-xs text-zinc-500 dark:text-zinc-400">Centro Preuniversitario</span>
            </span>
        </a>

        <flux:button x-data x-on:click="$flux.dark = !$flux.dark" variant="subtle" square aria-label="Cambiar tema">
            <x-slot name="icon">
                <x-ui.icon name="moon" size="5" class="text-zinc-500 dark:text-zinc-300" x-show="$flux.dark" x-cloak />
                <x-ui.icon name="sun" size="5" class="text-zinc-500 dark:text-zinc-300" x-show="!$flux.dark" x-cloak />
            </x-slot>
        </flux:button>
    </div>
</header>

<div class="mx-auto max-w-5xl px-5 pt-8 pb-2">
    <h1 class="text-2xl font-semibold tracking-tight text-balance text-zinc-900 dark:text-white">{{ $titulo }}</h1>
    @if ($subtitulo)
        <p class="mt-1 text-sm text-pretty text-zinc-500 dark:text-zinc-400">{{ $subtitulo }}</p>
    @endif
</div>
