@props ([
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'iconLeading' => null,
    'invalid' => null,
    'disabled' => false,
    'readonly' => false,
    'rows' => 4,
    'resize' => true,
    'autoResize' => false,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;
    $textareaId = $attributes->get('id') ?? $name ?? uniqid('textarea-', false);
    $error ??= ($name ? $errors->first($name) : null);
    $invalid ??= filled($error) || (bool) $invalid;
$textareaValue = (! is_null($value) && ! $attributes->has('wire:model') && ! $attributes->has('wire:model.live') && ! $attributes->has('wire:model.blur') && ! $attributes->has('wire:model.lazy') && ! $attributes->has('wire:model.defer'))
        ? $value
        : '';

    $paddingLeft = $iconLeading ? 'ps-10' : 'ps-3';
    $textareaClasses = \App\Helpers\Flux::classes()
        ->add('peer block w-full rounded-lg border disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add('bg-white dark:bg-zinc-800 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500')
        ->add('text-base sm:text-sm min-h-28 py-3 leading-5.5')
        ->add($paddingLeft)
        ->add('pe-3')
        ->add($floating ? 'pt-7 placeholder:text-transparent' : '')
        ->add($invalid
            ? 'border-red-500'
            : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5')
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add($readonly ? 'bg-zinc-50 dark:bg-zinc-900/60' : '')
        ->add($autoResize ? 'resize-none overflow-hidden' : ($resize ? 'resize-y' : 'resize-none'))
        ->add($attributes->pluck('class:input'));

    $leadingIconClasses = $invalid
        ? 'text-red-400'
        : 'text-zinc-400 dark:text-white/60';

    $placeholder = $floating ? ' ' : ($placeholder ?? $label ?? '');
@endphp

<x-ui.field
    :name="$name"
    :label="$label"
    :for="$textareaId"
    :hint="$hint"
    :error="$error"
    :required="$required"
    :floating="$floating"
    :invalid="$invalid"
    :disabled="$disabled"
    :icon-leading="$iconLeading"
>
    @if ($iconLeading)
        <div class="pointer-events-none absolute inset-s-0 top-3 z-10 flex items-center justify-center ps-3 text-xs">
            <x-ui.icon :name="$iconLeading" size="5" class="{{ $leadingIconClasses }}" />
        </div>
    @endif

    <textarea
        id="{{ $textareaId }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if ($autoResize)
            x-data
            x-init="
                $el.style.height = 'auto';
                $el.style.height = `${$el.scrollHeight}px`;
            "
            x-on:input="
                $el.style.height = 'auto';
                $el.style.height = `${$el.scrollHeight}px`;
            "
        @endif
        @disabled ($disabled)
        @readonly ($readonly)
        {{ $attributes->merge(['class' => $textareaClasses]) }}
        >{{ $textareaValue }}</textarea
    >
</x-ui.field>
