@props (['href' => null, 'active' => false])

@if ($href && !$active)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => 'hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors']) }}
    >
        {{ $slot }}
    </a>
@else
    <span {{ $attributes->merge(['class' => 'text-zinc-800 dark:text-zinc-200 font-regular']) }}> {{ $slot }} </span>
@endif
