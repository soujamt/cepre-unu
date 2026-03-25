@props([
    'headerClass' => 'px-4 pt-2 pb-3',
    'bodyClass' => 'rounded-md border border-zinc-200 bg-white px-4 py-4 dark:border-zinc-700 dark:bg-zinc-800',
    'footerClass' => 'px-4 pt-3 pb-2',
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-zinc-100 p-1 shadow-xs dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if (isset($header))
        <div class="{{ $headerClass }}">{{ $header }}</div>
    @endif

    <div class="{{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="{{ $footerClass }}">{{ $footer }}</div>
    @endif
</div>
