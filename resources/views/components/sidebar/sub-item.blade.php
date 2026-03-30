@props (['href' => '#', 'active' => false, 'label'])

<a
    href="{{ $href }}"
    @click="if (window.innerWidth < 768) sidebarOpen = false;"
    class="flex h-10 items-center gap-3 rounded-lg border px-4 py-1 text-sm font-regular uppercase group
    {{ $active
       ? 'bg-index-500 text-white border-index-600 shadow-sm shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:shadow-black/20'
       : 'text-zinc-600 border-zinc-50 hover:text-zinc-600 hover:bg-index-500/10 hover:border-index-600/10 dark:text-zinc-200 dark:border-zinc-800 hover:inset-shadow-xs hover:inset-shadow-white/20 dark:hover:bg-index-50/5 dark:hover:text-zinc-200 dark:hover:border-black/5'
    }}"
    {{ $attributes }}
    wire:navigate
>
    <span
        class="size-2 rounded-full transition-colors {{ $active ? 'bg-white shadow-accent' : 'bg-zinc-500 dark:bg-zinc-300' }}"
    ></span>
    {{ $label }}
</a>
