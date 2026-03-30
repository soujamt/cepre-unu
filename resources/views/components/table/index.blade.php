<div {{ $attributes->class(['min-w-full']) }}>
    <div
        class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900/40 [&::-webkit-scrollbar]:h-2 [&::-webkit-scrollbar-thumb]:rounded-none [&::-webkit-scrollbar-thumb]:bg-zinc-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-700 [&::-webkit-scrollbar-track]:bg-zinc-100 dark:[&::-webkit-scrollbar-track]:bg-zinc-800"
    >
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            {{ $slot }}
        </table>
    </div>
</div>
