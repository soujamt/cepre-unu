@props ([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'iconLeading' => null,
    'iconTrailing' => null,
    'invalid' => null,
    'disabled' => false,
    'readonly' => false,
    'loading' => false,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;
    $inputId = $attributes->get('id') ?? $name ?? uniqid('input-', false);
    $error ??= ($name ? $errors->first($name) : null);
    $invalid ??= filled($error) || (bool) $invalid;
    $hasVisibilityToggle = $type === 'password' && ! $disabled && ! $readonly;

    $hasLeadingIcon = filled($iconLeading);
    $hasTrailingIcon = filled($iconTrailing) || $loading || $hasVisibilityToggle;

    $paddingLeft = $hasLeadingIcon ? 'ps-10' : 'ps-3';
    $paddingRight = $hasTrailingIcon ? 'pe-10' : 'pe-3';
    $heightClasses = $floating ? 'text-base sm:text-sm h-12 pt-5.5 pb-1.5 leading-5.5' : 'text-base sm:text-sm py-2 h-10 leading-5.5';

    $inputClasses = \App\Helpers\Flux::classes()
        ->add('peer block w-full rounded-lg border disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add('bg-white dark:bg-zinc-800 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500')
        ->add($heightClasses)
        ->add($paddingLeft)
        ->add($paddingRight)
        ->add($floating ? 'placeholder:text-transparent' : '')
        ->add($invalid
            ? 'border-red-500'
            : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5')
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add($readonly ? 'bg-zinc-50 dark:bg-zinc-900/60' : '')
        ->add($attributes->pluck('class:input'));

    $leadingIconClasses = $invalid
        ? 'text-red-400'
        : 'text-zinc-400 dark:text-white/60';

    $trailingIconClasses = $loading
        ? 'text-accent'
        : ($invalid ? 'text-red-400' : 'text-zinc-400 dark:text-white/60');

    $placeholder = $floating ? ' ' : ($placeholder ?? $label ?? '');
@endphp

<x-ui.field
    :name="$name"
    :label="$label"
    :for="$inputId"
    :hint="$hint"
    :error="$error"
    :required="$required"
    :floating="$floating"
    :invalid="$invalid"
    :disabled="$disabled"
    :icon-leading="$iconLeading"
    :wrapper-data="$hasVisibilityToggle ? '{ showPassword: false }' : null"
>
    @if ($iconLeading)
        <div
            class="pointer-events-none absolute inset-y-0 inset-s-0 z-10 flex items-center justify-center ps-3 text-xs"
        >
            <x-ui.icon :name="$iconLeading" size="5" class="{{ $leadingIconClasses }}" />
        </div>
    @endif

    <input
        id="{{ $inputId }}"
        x-bind:type="{{ $hasVisibilityToggle ? "showPassword ? 'text' : '".e($type)."'" : "'".e($type)."'" }}"
        @if (! is_null($value) && ! $attributes->has('wire:model') && ! $attributes->has('wire:model.live') && ! $attributes->has('wire:model.blur') && ! $attributes->has('wire:model.lazy') && ! $attributes->has('wire:model.defer')) value="{{ $value }}" @endif
        placeholder="{{ $placeholder }}"
        @disabled ($disabled)
        @readonly ($readonly)
        {{ $attributes->merge(['class' => $inputClasses]) }}
    />

    @if ($loading || $iconTrailing || $hasVisibilityToggle)
        <div class="absolute inset-y-0 inset-e-0 z-10 flex items-center justify-center gap-x-2 pe-3 text-xs">
            @if ($loading)
                <div class="pointer-events-none">
                    <svg
                        class="size-5 animate-spin {{ $trailingIconClasses }}"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                </div>
            @endif

            @if ($hasVisibilityToggle)
                <button
                    type="button"
                    x-on:click="showPassword = !showPassword"
                    class="flex items-center justify-center text-zinc-400 transition-colors hover:text-zinc-600 focus:outline-none dark:text-white/60 dark:hover:text-white"
                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                >
                    <span x-show="!showPassword">
                        <x-ui.icon name="eye" size="5" />
                    </span>
                    <span x-show="showPassword" x-cloak>
                        <x-ui.icon name="eye-slash" size="5" />
                    </span>
                </button>
            @elseif ($iconTrailing)
                <div class="pointer-events-none">
                    <x-ui.icon :name="$iconTrailing" size="5" class="{{ $trailingIconClasses }}" />
                </div>
            @endif
        </div>
    @endif
</x-ui.field>
