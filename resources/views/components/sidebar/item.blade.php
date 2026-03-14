@props (['href' => '#', 'active' => false, 'label'])

<a
    href="{{ $href }}"
    @click="if (window.innerWidth < 768) sidebarOpen = false;"
    class="h-10 sm:h-10 flex items-center gap-2 px-2 py-1 text-sm font-regular rounded-lg group border
    {{ $active
       ? 'bg-index-500 text-white border-index-600 shadow-sm shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:shadow-black/20'
       : 'text-zinc-600 border-zinc-100 hover:text-zinc-600 hover:bg-index-500/10 hover:border-index-600/10 dark:text-zinc-200 dark:border-zinc-800 hover:inset-shadow-xs hover:inset-shadow-white/20 dark:hover:bg-index-50/5 dark:hover:text-zinc-200 dark:hover:border-black/5'
    }}"
    {{ $attributes }}
    wire:navigate
>
    <div
        class="size-7 group flex items-center justify-center rounded-md text-white border
        {{ $active
            ? 'bg-index-500 border-index-600/50 shadow-sm shadow-white/20 inset-shadow-xs inset-shadow-white/30'
            : 'bg-index-500/15 border-index-600/15 shadow-sm shadow-white/60 group-hover:shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:bg-index-50/5 dark:border-black/5 dark:shadow-black/5 dark:group-hover:shadow-black/5'
        }}
        "
    >
        {{ $icon ?? $slot }}
    </div>
    {{ $label }}
</a>
