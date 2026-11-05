<div {{ $attributes->class(['min-w-full']) }}>
    <div
        class="relative overflow-x-auto rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none [&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-zinc-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700 [&::-webkit-scrollbar-track]:bg-transparent"
    >
        <table class="min-w-full divide-y divide-zinc-200/80 text-sm dark:divide-white/[0.06]">
            {{ $slot }}
        </table>
    </div>
</div>
