@props (['label', 'active' => false, 'icon' => null])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="w-full">
    <button
        @click="open = !open"
        type="button"
        class="font-regular group flex h-10 w-full items-center gap-2 rounded-lg border px-2 py-1 text-sm"
        :class="{
            'bg-index-500/10 text-zinc-600 border-index-600/10 shadow-sm shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:inset-shadow-white/20 dark:bg-index-50/5 dark:text-zinc-200 dark:border-black/5 dark:shadow-black/10':
                open,
            'text-zinc-600 border-zinc-50 hover:text-zinc-600 hover:bg-index-500/10 hover:border-index-600/10 dark:text-zinc-200 dark:border-zinc-800 hover:inset-shadow-xs hover:inset-shadow-white/20 dark:hover:bg-index-50/5 dark:hover:text-zinc-200 dark:hover:border-black/5':
                !open,
        }"
    >
        <div
            class="flex size-7 items-center justify-center rounded-md border text-white"
            :class="{
                'bg-index-500/15 border-index-600/15 shadow-sm shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:bg-index-50/5 dark:border-black/5 dark:shadow-black/5':
                    open,
                'bg-index-500/15 border-index-600/15 shadow-sm shadow-white/60 group-hover:shadow-white/20 inset-shadow-xs inset-shadow-white/30 dark:bg-index-50/5 dark:border-black/5 dark:shadow-black/5 dark:group-hover:shadow-black/5':
                    !open,
            }"
        >
            @if ($icon)
                <x-ui.icon :name="$icon" size="4" class="{{ $active ? 'text-zinc-600 dark:text-white' : 'text-zinc-600 dark:text-white' }}" />
            @endif
        </div>

        <span>{{ $label }}</span>

        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            width="14"
            height="14"
            color="currentColor"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="ml-auto"
            :class="{ 'rotate-180 text-zinc-600': open, 'text-zinc-600 group-hover:text-zinc-500': !open }"
        >
            <path d="M18 9.00005C18 9.00005 13.5811 15 12 15C10.4188 15 6 9 6 9" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mt-1 flex flex-col space-y-1 pl-2"
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
