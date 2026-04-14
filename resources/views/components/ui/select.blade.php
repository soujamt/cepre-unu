@props ([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'iconLeading' => null,
    'iconTrailing' => 'caret-down',
    'invalid' => null,
    'disabled' => false,
    'loading' => false,
    'placeholder' => 'Seleccionar...',
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;
    $selectId = $attributes->get('id') ?? $name ?? uniqid('select-', false);
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
        ->add('w-full min-w-0 rounded-lg border text-left disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add('bg-white dark:bg-zinc-800 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 dark:text-zinc-300')
        ->add($floating ? 'h-12 pt-5.5 pb-1.5 text-base sm:text-sm leading-5.5' : 'h-10 py-2 text-base sm:text-sm leading-5.5')
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
            selected: @if($wireModel) @entangle($attributes->wire('model')) @else null @endif,

            init() {
                this.syncOptionsFromDOM();

                const observer = new MutationObserver((mutations) => {
                    const hasOptionChange = mutations.some(
                        m => m.type === 'childList' && (m.addedNodes.length > 0 || m.removedNodes.length > 0)
                    );

                    if (hasOptionChange) {
                        this.syncOptionsFromDOM();
                    }
                });

                const select = this.$refs.realSelect;
                if (select) {
                    observer.observe(select, { childList: true });
                }

                if (this.selected === null && ! this.$wire) {
                    this.loadValueFromSelect();
                }

                this.$watch('selected', () => this.syncSelectVisuals());
            },

            syncOptionsFromDOM() {
                const select = this.$refs.realSelect;
                if (! select) return;

                this.options = Array.from(select.options)
                    .filter(option => option.value !== '' && option.text.trim() !== '')
                    .map(option => ({ value: option.value, label: option.text }));
            },

            loadValueFromSelect() {
                const select = this.$refs.realSelect;
                if (! select) return;

                this.selected = select.value === '' ? null : select.value;
            },

            syncSelectVisuals() {
                const select = this.$refs.realSelect;
                if (! select) return;

                select.value = this.selected ?? '';
            },

            get selectedLabels() {
                if (this.selected === null || this.selected === '') {
                    return @js($floating) ? '' : this.placeholder;
                }

                const option = this.options.find(item => item.value == this.selected);

                return option ? (option.label || option.value) : (@js($floating) ? '' : this.placeholder);
            },

            selectedIsEmpty() {
                return this.selected === null || this.selected === '' || this.selected === undefined;
            },

            toggleOption(value) {
                this.selected = value;
                this.open = false;
                this.$dispatch('ui-select-changed', {
                    name: @js($name),
                    value,
                });
            },

            isSelected(value) {
                return this.selected == value;
            },

            labelRaised() {
                return this.open || ! this.selectedIsEmpty();
            },
        }"
        x-init="init()"
        @click.outside="open = false"
        class="group/select relative block w-full min-w-0"
    >
        <select id="{{ $selectId }}" x-ref="realSelect" class="hidden">
            <option value="">{{ $placeholder }}</option>
            {{ $slot }}
        </select>

        @if ($iconLeading)
            <div
                class="pointer-events-none absolute inset-y-0 inset-s-0 z-10 flex items-center justify-center ps-3 text-xs"
            >
                <x-ui.icon
                    :name="$iconLeading"
                    size="5"
                    class="{{ $invalid ? 'text-red-400' : 'text-zinc-400 dark:text-white/60' }}"
                />
            </div>
        @endif

        @if ($label && $floating)
            <label
                for="{{ $selectId }}"
                class="pointer-events-none absolute z-10 origin-left transition-all duration-200 ease-out {{ $labelOffsetClasses }} {{ $floatingLabelColorClasses }}"
                x-bind:class="
                    labelRaised() ? 'top-1.5 translate-y-0 text-[10px]' : 'top-1/2 -translate-y-1/2 text-[13px]'
                "
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
            @click="
                syncOptionsFromDOM();
                open = !open;
            "
            @disabled ($disabled)
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.lazy', 'wire:model.defer', 'placeholder'])->class([$controlClasses, 'min-w-0']) }}
        >
            <span
                x-text="selectedLabels"
                :class="{ 'text-zinc-400 dark:text-white/60': selectedIsEmpty() }"
                class="block w-full min-w-0 truncate"
                @class (['mt-3' => $floating])
            ></span>
        </button>

        @if ($loading || $iconTrailing)
            <div
                class="pointer-events-none absolute inset-y-0 inset-e-0 z-10 flex items-center justify-center pe-3 text-xs"
            >
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
                    <x-ui.icon
                        :name="$iconTrailing"
                        size="5"
                        class="text-zinc-400 transition-transform duration-200 dark:text-white/60"
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
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50': !isSelected(
                                option.value,
                            ),
                        }"
                        class="flex w-full cursor-pointer items-center justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        <span class="truncate" x-text="option.label || option.value"></span>
                        <template x-if="isSelected(option.value)">
                            <x-ui.icon name="check-circle" size="4" class="text-accent shrink-0" />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-ui.field>
