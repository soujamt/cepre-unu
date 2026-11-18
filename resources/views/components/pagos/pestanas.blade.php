@props (['activa'])

<nav aria-label="Secciones de pagos" class="flex gap-1 rounded-xl border border-zinc-200/80 bg-zinc-100/70 p-1 dark:border-white/[0.06] dark:bg-zinc-950/60">
    @foreach ([
        ['clave' => 'pagos', 'label' => 'Pagos', 'href' => route('pagos.index'), 'icon' => 'wallet'],
        ['clave' => 'importaciones', 'label' => 'Importaciones TXT', 'href' => route('pagos.importaciones'), 'icon' => 'upload'],
    ] as $pestana)
        <a
            href="{{ $pestana['href'] }}"
            wire:navigate
            @if ($activa === $pestana['clave']) aria-current="page" @endif
            @class ([
                'flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition-colors',
                'bg-white font-medium text-zinc-900 shadow-xs dark:bg-zinc-800 dark:text-white' => $activa === $pestana['clave'],
                'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $activa !== $pestana['clave'],
            ])
        >
            <x-ui.icon :name="$pestana['icon']" size="4" />
            {{ $pestana['label'] }}
        </a>
    @endforeach
</nav>
