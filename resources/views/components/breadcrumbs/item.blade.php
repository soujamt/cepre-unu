@props (['href' => null, 'active' => false])

@if ($href && ! $active)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => 'transition-colors hover:text-zinc-900 dark:hover:text-zinc-100']) }}
    >
        {{ $slot }}
    </a>
@else
    <span {{ $attributes->merge(['class' => 'font-medium text-zinc-700 dark:text-zinc-200']) }}>{{ $slot }}</span>
@endif
