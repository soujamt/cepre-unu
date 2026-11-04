@props (['href' => '#', 'active' => false, 'label', 'icon' => null])

<a
    href="{{ $href }}"
    @click="if (window.innerWidth < 768) sidebarOpen = false;"
    @class ([
        'group flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-sm transition-colors',
        'bg-white font-medium text-zinc-900 shadow-xs ring-1 ring-zinc-200/80 dark:bg-zinc-800 dark:text-white dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] dark:ring-white/[0.08]' => $active,
        'text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100' => ! $active,
    ])
    {{ $attributes }}
    wire:navigate
>
    @if ($icon)
        <x-ui.icon
            :name="$icon"
            size="5"
            :class="$active
                ? 'text-index-500 transition-colors dark:text-index-400'
                : 'text-zinc-400 transition-colors group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300'"
        />
    @endif
    <span class="inline-block lowercase first-letter:uppercase">{{ $label }}</span>
</a>
