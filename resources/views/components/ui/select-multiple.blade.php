@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'iconLeading' => null,
    'iconTrailing' => 'chevron-down',
    'invalid' => null,
    'disabled' => false,
    'loading' => false,
    'placeholder' => 'Seleccionar...',
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;
    $selectId = $attributes->get('id') ?? $name ?? uniqid('select-multiple-', false);
    $error ??= ($name ? $errors->first($name) : null);
    $invalid ??= filled($error) || (bool) $invalid;

    $hasLeadingIcon = filled($iconLeading);
    $hasTrailingIcon = filled($iconTrailing) || $loading;
    $paddingLeft = $hasLeadingIcon ? 'ps-10' : 'ps-3';
    $paddingRight = $hasTrailingIcon ? 'pe-10' : 'pe-3';
    $labelOffsetClasses = $hasLeadingIcon ? 'start-11' : 'start-3.5';
    $floatingLabelColorClasses = $invalid
        ? 'text-red-500'
        : 'text-zinc-500 dark:text-zinc-400';
    $controlClasses = \App\Helpers\Flux::classes()
        ->add('w-full rounded-lg border text-left text-base sm:text-sm disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add('bg-white dark:bg-zinc-800 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 dark:text-zinc-300')
        ->add($floating ? 'min-h-12 pt-7 pb-2' : 'min-h-10 py-2')
        ->add($paddingLeft)
        ->add($paddingRight)
        ->add($invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5')
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add('cursor-pointer overflow-hidden relative')
        ->add($attributes->pluck('class:input'));
@endphp

<x-ui.field
    :name="$name"
    :label="$floating ? null : $label"
    :for="$selectId"
    :hint="$hint"
    :error="$error"
    :required="$required"
    :invalid="$invalid"
    :disabled="$disabled"
>
    <div
        x-data="{
            open: false,
            options: [],
            placeholder: @js($placeholder),
            selected: @if($wireModel) @entangle($attributes->wire('model')) @else [] @endif,

            init() {
                this.syncOptionsFromDOM();

                if ((! Array.isArray(this.selected) || this.selected.length === 0) && ! this.$wire) {
                    this.loadValueFromSelect();
                }

                this.$watch('selected', () => this.syncSelectVisuals());

                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => this.syncOptionsFromDOM());
                });
            },

            syncOptionsFromDOM() {
                const select = this.$refs.realSelect;
                if (! select) return;

                this.options = Array.from(select.options)
                    .filter(option => option.value !== '')
                    .map(option => ({ value: option.value, label: option.text }));
            },

            loadValueFromSelect() {
                const select = this.$refs.realSelect;
                if (! select) return;

                this.selected = Array.from(select.selectedOptions).map(option => option.value).filter(Boolean);
            },

            syncSelectVisuals() {
                const select = this.$refs.realSelect;
                if (! select) return;

                const values = Array.isArray(this.selected) ? this.selected : [];
                Array.from(select.options).forEach(option => option.selected = values.includes(option.value));
            },

            selectedIsEmpty() {
                return ! Array.isArray(this.selected) || this.selected.length === 0;
            },

            toggleOption(value) {
                let values = Array.isArray(this.selected) ? [...this.selected] : [];
                const index = values.indexOf(value);

                if (index !== -1) values.splice(index, 1);
                else values.push(value);

                this.selected = values;
            },

            isSelected(value) {
                return Array.isArray(this.selected) && this.selected.some(selected => selected == value);
            },

            removeTag(value, event) {
                event.stopPropagation();
                if (! Array.isArray(this.selected)) return;

                this.selected = this.selected.filter(selected => selected != value);
            },

            labelRaised() {
                return this.open || ! this.selectedIsEmpty();
            },
        }"
        x-init="init()"
        @click.outside="open = false"
        class="group/select relative block w-full"
    >
        <select id="{{ $selectId }}" x-ref="realSelect" multiple class="hidden">
            {{ $slot }}
        </select>

        @if ($iconLeading)
            <div class="pointer-events-none absolute inset-y-0 inset-s-0 z-10 flex items-center justify-center ps-3 text-xs">
                <x-ui.icon :name="$iconLeading" size="5" class="{{ $invalid ? 'text-red-400' : 'text-zinc-400 dark:text-white/60' }}" />
            </div>
        @endif

        @if ($label && $floating)
            <label
                for="{{ $selectId }}"
                class="pointer-events-none absolute z-10 origin-left transition-all duration-200 ease-out {{ $labelOffsetClasses }} {{ $floatingLabelColorClasses }}"
                x-bind:class="labelRaised() ? 'top-1.5 translate-y-0 text-[10px]' : 'top-1/2 -translate-y-1/2 text-[13px]'"
            >
                <span class="inline-flex items-center gap-1">
                    <span>{{ $label }}</span>

                    @if ($required)
                        <span class="text-red-500">*</span>
                    @endif
                </span>
            </label>
        @endif

        <button
            type="button"
            @click="open = ! open"
            @disabled($disabled)
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.change', 'wire:model.lazy', 'wire:model.defer', 'placeholder'])->class([$controlClasses]) }}
        >
            <div class="flex w-full flex-wrap gap-1.5 overflow-hidden py-0" @class(['mt-3' => $floating])>
                <template x-for="value in (Array.isArray(selected) ? selected : [])" :key="value">
                    <span class="inline-flex max-w-full items-center rounded-full bg-zinc-100 py-px pe-1.5 text-xs font-medium text-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                        <span class="max-w-25 truncate ps-2" x-text="options.find((option) => option.value == value)?.label || value"></span>
                        <button
                            type="button"
                            @click="removeTag(value, $event)"
                            class="ml-1 shrink-0 rounded-full p-0.5 hover:bg-zinc-200 focus:outline-none dark:hover:bg-zinc-600"
                        >
                            <flux:icon name="x-mark" class="size-3" />
                        </button>
                    </span>
                </template>

                <template x-if="selectedIsEmpty()">
                    <span
                        class="block truncate align-middle leading-5.5 text-zinc-400 dark:text-white/60"
                        x-text="@js($floating) ? '' : placeholder"
                    ></span>
                </template>
            </div>
        </button>

        @if ($loading || $iconTrailing)
            <div class="pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex items-center justify-center pe-3 text-xs">
                @if ($loading)
                    <svg
                        class="size-5 animate-spin {{ $invalid ? 'text-red-400' : 'text-accent' }}"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                @elseif (is_string($iconTrailing))
                    <flux:icon
                        :icon="$iconTrailing"
                        class="size-5 text-zinc-400 transition-transform duration-200 dark:text-white/60"
                        x-bind:class="{ 'rotate-180': open }"
                    />
                @endif
            </div>
        @endif

        <div
            x-cloak
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 mt-1 max-h-45 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-white/10 dark:bg-zinc-800"
        >
            <div class="p-1">
                <template x-if="options.length === 0">
                    <div class="p-2 text-sm text-zinc-500 dark:text-zinc-400">Sin opciones disponibles.</div>
                </template>

                <template x-for="option in options" :key="option.value">
                    <button
                        type="button"
                        @click="toggleOption(option.value)"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-900/50 text-zinc-900 dark:text-white': isSelected(option.value),
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50': ! isSelected(option.value),
                        }"
                        class="flex w-full cursor-pointer items-center justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        <span class="truncate" x-text="option.label || option.value"></span>
                        <template x-if="isSelected(option.value)">
                            <x-ui.icon name="check-circle" size="4" class="shrink-0 text-accent" />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-ui.field>
