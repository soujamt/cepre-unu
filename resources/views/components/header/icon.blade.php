@props (['icon' => 'home'])

<div
    class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-index-100 bg-index-50 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] dark:border-index-500/20 dark:bg-index-500/10 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]"
>
    <x-ui.icon :name="$icon" size="5" class="text-index-500 dark:text-index-300" />
</div>
