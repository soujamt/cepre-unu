@props (['left' => null, 'right' => null])

<header
    class="z-10 flex h-16 items-center justify-between border-b border-zinc-200 bg-white px-5 dark:border-zinc-800 dark:bg-zinc-950"
>
    <!-- Left -->
    <div class="flex items-center gap-3">{{ $left ?? '' }}</div>

    <!-- Right -->
    <div class="flex items-center gap-3">{{ $right ?? $slot }}</div>
</header>
