@props (['icon' => 'home-3'])

<div
    class="flex size-10 items-center justify-center rounded-lg border border-zinc-100 bg-zinc-50 shadow-xs inset-shadow-xs inset-shadow-white/15 dark:border-zinc-800 dark:bg-zinc-900/50 dark:shadow-black/40"
>
    <x-ui.icon
        :name="$icon"
        size="6"
        class="text-index-500 dark:text-index-200"
    />
</div>
