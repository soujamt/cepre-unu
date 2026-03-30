@props ([
    'name' => null,
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'invalid' => null,
    'disabled' => false,
    'iconLeading' => null,
    'wrapperData' => null,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;
    $for ??= $name;
    $error ??= ($name ? $errors->first($name) : null);
    $invalid ??= filled($error);

    $labelOffsetClasses = $iconLeading ? 'start-11' : 'start-3.5';

    $floatingLabelBaseClasses = 'top-1/2 -translate-y-1/2 text-[13px] peer-focus:top-1.5 peer-focus:translate-y-0 peer-focus:text-[10px] peer-[:not(:placeholder-shown)]:top-1.5 peer-[:not(:placeholder-shown)]:translate-y-0 peer-[:not(:placeholder-shown)]:text-[10px]';

    $floatingLabelColorClasses = $invalid
        ? 'text-red-500 peer-focus:text-red-500'
        : 'text-zinc-500 peer-focus:text-accent dark:text-zinc-400 dark:peer-focus:text-white';

    $staticLabelClasses = $invalid
        ? 'text-red-500'
        : 'text-zinc-700 dark:text-zinc-200';

    $hintClasses = $invalid
        ? 'ps-3 text-red-500 dark:text-red-400'
        : 'ps-3 text-zinc-500 dark:text-zinc-400';
@endphp

<div
    {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.change', 'wire:model.lazy', 'wire:model.defer'])->class('grid gap-2') }}
>
    @if ($label && ! $floating)
        <label
            @if ($for) for="{{ $for }}" @endif
            class="inline-flex items-center gap-1 text-sm font-medium {{ $staticLabelClasses }}"
        >
            <span>{{ $label }}</span>

            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative" @if ($wrapperData) x-data="{{ $wrapperData }}" @endif>
        {{ $slot }}

        @if ($label && $floating)
            <label
                @if ($for) for="{{ $for }}" @endif
                class="pointer-events-none absolute z-10 origin-left transition-all duration-200 ease-out {{ $labelOffsetClasses }} {{ $floatingLabelBaseClasses }} {{ $floatingLabelColorClasses }}"
            >
                <span class="inline-flex items-center gap-1">
                    <span>{{ $label }}</span>

                    @if ($required)
                        <span class="text-red-500">*</span>
                    @endif
                </span>
            </label>
        @endif
    </div>

    @if ($invalid && $error)
        <p class="flex items-center gap-1 text-xs font-medium {{ $hintClasses }}">
            <x-ui.icon name="warning-circle" size="4" />
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="text-xs {{ $hintClasses }}">{{ $hint }}</p>
    @endif
</div>
