@props([
    'headerClass' => 'px-3 pt-2 pb-2.5',
    'bodyClass' => 'rounded-[0.65rem] border border-zinc-200/80 bg-white px-4 py-4 shadow-xs dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none',
    'footerClass' => 'px-3 pt-2.5 pb-1.5',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200/80 bg-zinc-50 p-1 dark:border-white/[0.06] dark:bg-zinc-950/60']) }}>
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
