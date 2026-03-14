<aside class="z-10 flex w-70 flex-col border-r border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
    <!-- Workspace Header -->
    <div class="flex h-16 items-center border-b border-zinc-200 px-5 dark:border-zinc-700">
        <div class="flex items-center gap-3 font-medium text-zinc-700 dark:text-zinc-300">{{ $header ?? '' }}</div>
    </div>

    <!-- Scrollable Menu -->
    <div
        class="[&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-zinc-800 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-300 flex-1 space-y-8 overflow-y-auto px-3 py-5"
    >
        <nav class="space-y-1">{{ $slot }}</nav>
    </div>

    <!-- Footer -->
    @if (isset($footer))
        <div class="h-14 w-full border-t border-zinc-200 bg-zinc-100 px-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex h-full w-full items-center">{{ $footer }}</div>
        </div>
    @endif
    <div class="h-14 w-full border-t border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex h-full items-center justify-between px-3">
            <div class="flex flex-col">
                <span class="text-[10px] font-normal tracking-wider text-zinc-500 uppercase dark:text-zinc-400">CEPRE UNIA</span>
                <span class="text-[10px] text-zinc-500 dark:text-zinc-400">© {{ date('Y') }}</span>
            </div>

            <flux:badge size="sm" class="font-mono border border-zinc-400 dark:border-zinc-500">{{ config('app.version') }}</flux:badge>
        </div>
    </div>
</aside>
