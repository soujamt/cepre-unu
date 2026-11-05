@props (['href' => '#', 'active' => false, 'label'])

<a
    href="{{ $href }}"
    @click="if (window.innerWidth < 768) sidebarOpen = false;"
    @class ([
        'relative flex h-8 items-center rounded-md px-2.5 text-[13px] transition-colors',
        'bg-index-50 font-medium text-index-600 dark:bg-index-500/10 dark:text-index-300' => $active,
        'text-zinc-500 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100' => ! $active,
    ])
    {{ $attributes }}
    wire:navigate
>
    @if ($active)
        <span class="absolute -start-[11px] top-1.5 bottom-1.5 w-0.5 rounded-full bg-index-500"></span>
    @endif
    <span class="inline-block lowercase first-letter:uppercase">{{ $label }}</span>
</a>
