@props (['label', 'active' => false, 'icon' => null])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="w-full">
    <button
        @click="open = !open"
        type="button"
        @class ([
            'group flex h-9 w-full items-center gap-2.5 rounded-lg px-2.5 text-sm transition-colors hover:bg-zinc-200/60 hover:text-zinc-900 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
            'font-medium text-zinc-900 dark:text-white' => $active,
            'text-zinc-600 dark:text-zinc-400' => ! $active,
        ])
        :aria-expanded="open"
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

        <span class="inline-block text-start lowercase first-letter:uppercase">{{ $label }}</span>

        <x-ui.icon
            name="chevron-down"
            size="4"
            class="ms-auto text-zinc-400 transition-transform duration-200 dark:text-zinc-500"
            x-bind:class="open ? 'rotate-180' : ''"
        />
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="relative ms-[1.2rem] mt-0.5 mb-1 flex flex-col gap-0.5 border-s border-zinc-200 ps-2.5 dark:border-white/[0.08]"
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
