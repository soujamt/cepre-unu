@props ([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'floating' => false,
    'invalid' => null,
    'disabled' => false,
    'action',
    'placeholder' => 'Buscar...',
    'iconLeading' => 'magnifying-glass',
    'apiKey' => null,
])

@php
    $name ??= $attributes->whereStartsWith('wire:model')->first();
    $inputId = $attributes->get('id') ?? $name ?? uniqid('select-search-', false);
    $error ??= ($name ? $errors->first($name) : null);
    $invalid ??= filled($error) || (bool) $invalid;
    $labelOffsetClasses = filled($iconLeading) ? 'start-11' : 'start-3.5';
    $floatingLabelColorClasses = $invalid
        ? 'text-red-500'
        : 'text-zinc-500 dark:text-zinc-400';

    $controlClasses = \App\Helpers\Flux::classes()
        ->add('block w-full rounded-lg border disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add('bg-white dark:bg-zinc-800 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500')
        ->add($floating ? 'text-base sm:text-sm h-12 pt-5.5 pb-1.5 leading-5.5' : 'text-base sm:text-sm py-2 h-10 leading-5.5')
        ->add('ps-10 pe-10')
        ->add($floating ? 'placeholder:text-transparent' : '')
        ->add($invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5')
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add('truncate')
        ->add($attributes->pluck('class:input'));
@endphp

<x-ui.field
    :name="$name"
    :for="$inputId"
    :hint="$hint"
    :error="$error"
    :required="$required"
    :invalid="$invalid"
    :disabled="$disabled"
>
    <div
        x-data="{
            show: false,
            options: [],
            value: @entangle($attributes->wire('model')),
            search: '',
            searching: false,
            url: @js($action),
            apiKey: @js($apiKey),

            init() {
                this.$watch('value', (value) => {
                    if (! value) {
                        this.search = '';
                    }
                });
            },

            fetchOptions() {
                if (! this.search || this.search.length < 2) {
                    this.options = [];
                    return;
                }

                this.searching = true;

                const headers = {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                };

                if (this.apiKey) {
                    headers['X-API-KEY'] = this.apiKey;
                }

                fetch(this.url + '?query=' + encodeURIComponent(this.search), {
                    method: 'GET',
                    headers,
                })
                    .then(response => {
                        if (! response.ok) throw new Error('Error en la API');
                        return response.json();
                    })
                    .then(data => {
                        this.options = data;
                        this.show = this.options.length > 0;
                        this.searching = false;
                    })
                    .catch(() => {
                        this.options = [];
                        this.searching = false;
                    });
            },

            selectOption(option) {
                this.value = option.value;
                this.search = option.label;
                this.show = false;
            },

            clear() {
                this.value = null;
                this.search = '';
                this.options = [];
                this.show = false;
                this.$refs.searchInput.focus();
            },

            openSearch() {
                if (this.search && this.search.length >= 2 && this.options.length > 0) {
                    this.show = true;
                }
            },

            labelRaised() {
                return this.show || !! this.search || !! this.value;
            },
        }"
        @click.outside="show = false"
        class="group/select relative block w-full"
    >
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
                for="{{ $inputId }}"
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

        <input
            id="{{ $inputId }}"
            x-ref="searchInput"
            type="text"
            x-model="search"
            @input.debounce.400ms="fetchOptions()"
            @focus="openSearch()"
            @keydown.escape="show = false"
            placeholder="{{ $floating ? '' : $placeholder }}"
            autocomplete="off"
            @disabled ($disabled)
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.change', 'wire:model.lazy', 'wire:model.defer'])->merge(['class' => $controlClasses]) }}
        />

        <div
            class="absolute inset-y-0 inset-e-0 z-10 flex items-center gap-x-1.5 pe-3 text-xs text-zinc-400 dark:text-white/60"
        >
            <div x-show="searching" x-cloak>
                <x-ui.icon name="spinner-gap" size="5" class="animate-spin" />
            </div>

            <button
                x-show="search && !searching"
                @click="clear()"
                type="button"
                class="text-zinc-400 transition-colors hover:text-red-500 focus:outline-none"
                x-cloak
            >
                <x-ui.icon name="x" size="5" />
            </button>

            <div x-show="!search && !searching" x-cloak class="pointer-events-none">
                <x-ui.icon name="caret-down" size="5" class="text-zinc-400 dark:text-white/60" />
            </div>
        </div>

        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-white/10 dark:bg-zinc-800"
            x-cloak
        >
            <div class="p-1">
                <template x-if="options.length === 0 && !searching">
                    <div class="p-2 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron coincidencias.</div>
                </template>

                <template x-for="option in options" :key="option.value">
                    <button
                        type="button"
                        @click="selectOption(option)"
                        :class="{
                            'bg-zinc-100 dark:bg-zinc-900/50 text-zinc-900 dark:text-white': value == option.value,
                            'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-900/50':
                                value != option.value,
                        }"
                        class="flex w-full cursor-pointer items-center justify-between rounded-md p-2 text-left text-sm transition duration-150 ease-in-out focus:outline-none"
                    >
                        <span class="truncate" x-text="option.label"></span>
                        <template x-if="value == option.value">
                            <x-ui.icon name="check-circle" size="4" class="text-accent shrink-0" />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-ui.field>
