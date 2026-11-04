<aside class="z-10 flex h-full w-68 flex-col bg-zinc-100 dark:bg-zinc-950">
    <!-- Workspace Header -->
    <div class="flex h-18 shrink-0 items-center px-4">
        <div class="flex w-full items-center gap-3">{{ $header ?? '' }}</div>
    </div>

    <!-- Scrollable Menu -->
    <div class="custom-scrollbar flex-1 overflow-y-auto px-3 pt-2 pb-4">
        <p class="px-2.5 pb-2 text-[11px] font-medium tracking-[0.08em] text-zinc-400 uppercase dark:text-zinc-500">
            Menú principal
        </p>
        <nav class="space-y-0.5">{{ $slot }}</nav>
    </div>

    <!-- Footer -->
    @if (isset($footer))
        <div class="hidden px-3 pb-2 md:block">
            <div
                class="rounded-xl border border-zinc-200/80 bg-white p-2.5 shadow-xs dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none"
            >
                {{ $footer }}
            </div>
        </div>
    @endif

    <div class="flex h-11 shrink-0 items-center justify-between px-5">
        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">CEPRE UNU © {{ date('Y') }}</span>
        <span
            class="rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400"
        >
            {{ config('app.version') }}
        </span>
    </div>
</aside>
