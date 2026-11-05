@props (['left' => null, 'right' => null])

<header
    class="z-10 flex h-16 shrink-0 items-center justify-between border-b border-zinc-200/80 px-4 sm:px-6 dark:border-white/[0.06]"
>
    <!-- Left -->
    <div class="flex min-w-0 items-center gap-3">{{ $left ?? '' }}</div>

    <!-- Right -->
    <div class="flex items-center gap-3">{{ $right ?? $slot }}</div>
</header>
