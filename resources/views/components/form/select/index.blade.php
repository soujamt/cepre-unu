@php
    $iconTrailing ??= $attributes->pluck('icon:trailing');
    $iconLeading ??= $attributes->pluck('icon:leading');
    $iconVariant ??= $attributes->pluck('icon:variant');
    $maskDynamic ??= $attributes->pluck('mask:dynamic');
@endphp

@props ([
    'name' => null,
    'iconVariant' => 'mini',
    'variant' => 'outline',
    'iconTrailing' => 'chevron-down',
    'iconLeading' => null,
    'maskDynamic' => null,
    'expandable' => null,
    'clearable' => null,
    'copyable' => null,
    'viewable' => null,
    'invalid' => null,
    'loading' => null,
    'type' => 'text',
    'mask' => null,
    'size' => null,
    'icon' => null,
    'kbd' => null,
    'options' => [],
    'multiple' => false,
])

@php
    // Detectamos si hay wire:model
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $name ??= $wireModel;

    // --- Lógica de Loading ---
    $wireObject = $attributes->wire('model');
    $wireTarget = null;

    if ($loading !== false) {
        if ($loading === true) {
            $loading = true;
        } elseif ($wireObject?->directive) {
            $loading = $wireObject->hasModifier('live');
            $wireTarget = $loading ? $wireObject->value() : null;
        } else {
            $wireTarget = $loading;
            $loading = (bool) $loading;
        }
    }

    $invalid ??= ($name && $errors->has($name));
    $iconLeading ??= $icon;
    $hasLeadingIcon = (bool) ($iconLeading);
    $isMultiSelect = (bool) $multiple;

    // ... (El resto de tus clases PHP se mantienen igual) ...
    $countOfTrailingIcons = collect([
        (bool) $iconTrailing, (bool) $kbd, (bool) $clearable,
        (bool) $copyable, (bool) $viewable, (bool) $expandable,
    ])->filter()->count();

    $iconClasses = \App\Helpers\Flux::classes()->add($iconVariant === 'outline' ? 'size-5' : '');

    $controlClasses = \App\Helpers\Flux::classes()
        ->add('w-full grid grid-cols-1 rounded-lg border disabled:shadow-none dark:shadow-none')
        ->add('appearance-none')
        ->add(match ($size) {
            default => 'text-base sm:text-sm py-2 ' . ($isMultiSelect ? 'min-h-10' : 'h-10 leading-5.5'),
            'sm' => 'text-sm py-1.5 ' . ($isMultiSelect ? 'min-h-8' : 'h-8 leading-4.5'),
            'xs' => 'text-xs py-1.5 ' . ($isMultiSelect ? 'min-h-6' : 'h-6 leading-4.5'),
        })
        ->add($hasLeadingIcon ? 'ps-10' : 'ps-3')
        ->add(match ($countOfTrailingIcons) {
            0 => 'pe-3', 1 => 'pe-10', 2 => 'pe-16', 3 => 'pe-23', 4 => 'pe-30', 5 => 'pe-37', 6 => 'pe-44',
        })
        ->add(match ($variant) {
            'outline' => 'bg-white dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:placeholder-zinc-500',
            'filled'  => 'bg-zinc-800/5 dark:bg-white/10 dark:disabled:bg-white/7 text-zinc-700 placeholder-zinc-500 disabled:placeholder-zinc-400 dark:text-zinc-200 dark:placeholder-white/60 dark:disabled:placeholder-white/40',
        })
        ->add(match ($variant) {
            'outline' => $invalid ? 'border-red-500' : 'shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5',
            'filled'  => $invalid ? 'border-red-500' : 'border-0',
        })
        ->add('focus:outline-hidden focus:ring-1 focus:ring-accent focus:ring-offset-1 focus:ring-offset-background')
        ->add($attributes->pluck('class:input'))
        ->add('text-left cursor-pointer overflow-hidden relative');

    $placeholder = $attributes->get('placeholder') ?? 'Seleccionar...';
@endphp

<flux:with-field :$attributes :$name>
    <div
        x-data="{
            open: false,
            multiple: {{ $multiple ? 'true' : 'false' }},
            options: [],
            placeholder: @js($placeholder),

            {{--
                SOLUCIÓN CLAVE:
                Usamos @entangle si existe wire:model. Esto conecta JS con PHP directamente.
                Si no hay wire:model, usamos 'null' y funcionará como select normal.
            --}}
            selected: @if($wireModel) @entangle($attributes->wire('model')) @else null @endif,

            init() {
                // Solo cargamos las OPCIONES del DOM, no el valor (el valor lo maneja entangle)
                this.syncOptionsFromDOM();

                // Si no hay wire:model, intentamos cargar el valor inicial del select HTML
                if (this.selected === null && !this.$wire) {
                   this.loadValueFromSelect();
                }

                // Sincronizar el select oculto cuando cambia 'selected' (para que funcione el submit nativo)
                this.$watch('selected', (value) => {
                    this.syncSelectVisuals();
                });

                // Si Livewire actualiza el DOM (ej: cambian las opciones dependientes), recargamos la lista
                Livewire.hook('morph.updated', () => {
                    this.$nextTick(() => {
                        this.syncOptionsFromDOM();
                    });
                });
            },

            syncOptionsFromDOM() {
                const select = this.$refs.realSelect;
                if (!select) return;

                this.options = Array.from(select.options)
                    .filter(o => o.value !== '' && (this.multiple || o.text.trim() !== ''))
                    .map((o) => ({ value: o.value, label: o.text }));
            },

            loadValueFromSelect() {
                const select = this.$refs.realSelect;
                if (!select) return;

                if (this.multiple) {
                    const selectedValues = Array.from(select.selectedOptions).map(o => o.value).filter(v => v !== '');
                    this.selected = selectedValues;
                } else {
                    this.selected = select.value === '' ? null : select.value;
                }
            },

            syncSelectVisuals() {
                const select = this.$refs.realSelect;
                if (!select) return;

                // Actualizamos el select oculto solo para consistencia visual/submit tradicional
                if (this.multiple) {
                    const values = Array.isArray(this.selected) ? this.selected : [];
                    Array.from(select.options).forEach(o => o.selected = values.includes(o.value));
                } else {
                    select.value = this.selected ?? '';
                }
            },

            get selectedLabels() {
                if (this.multiple) {
                    if (!Array.isArray(this.selected) || this.selected.length === 0) return this.placeholder;

                    const labels = this.options
                        .filter(opt => this.selected.some(s => s == opt.value))
                        .map(opt => opt.label || opt.value);

                    return labels.length ? labels.join(', ') : this.placeholder;
                }

                if (this.selected === null || this.selected === '') return this.placeholder;

                const opt = this.options.find(o => o.value == this.selected);
                return opt ? (opt.label || opt.value) : this.placeholder;
            },

            selectedIsEmpty() {
                if (this.multiple) return !Array.isArray(this.selected) || this.selected.length === 0;
                return this.selected === null || this.selected === '' || this.selected === undefined;
            },

            toggleOption(value) {
                if (this.multiple) {
                    // Copiamos el array para reactividad de Alpine
                    let values = Array.isArray(this.selected) ? [...this.selected] : [];
                    const index = values.indexOf(value);

                    if (index !== -1) values.splice(index, 1);
                    else values.push(value);

                    this.selected = values; // Entangle actualiza Livewire automáticamente aquí
                } else {
                    this.selected = value; // Entangle actualiza Livewire automáticamente aquí
                    this.open = false;
                }
            },

            isSelected(value) {
                if (this.multiple) {
                    return Array.isArray(this.selected) && this.selected.some(s => s == value);
                }
                return this.selected == value;
            },

            removeTag(value, event) {
                event.stopPropagation();
                if (!Array.isArray(this.selected)) return;
                this.selected = this.selected.filter(v => v != value);
            }
        }"
        x-init="init()"
        @click.outside="open = false"
        class="group/select relative block w-full"
    >
        {{--
            SELECT REAL (oculto)
            Nota: Quitamos wire:model de aquí para evitar conflictos dobles con @entangle.
            El @entangle en x-data ya maneja la comunicación.
        --}}
        <select x-ref="realSelect" @if ($multiple) multiple @endif class="hidden">
            @if (!$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>

        {{-- BOTÓN TRIGGER --}}
        <button
            type="button"
            @click="open = !open"
            {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'placeholder'])->class([$controlClasses]) }}
        >
            @if (is_string($iconLeading))
                <div
                    class="pointer-events-none absolute inset-s-0 top-0 bottom-0 flex items-center justify-center ps-3 text-xs text-zinc-400/75"
                >
                    <flux:icon :icon="$iconLeading" :variant="$iconVariant" :class="$iconClasses" />
                </div>
            @elseif ($iconLeading)
                <div
                    {{ $iconLeading->attributes->class('pointer-events-none absolute top-0 bottom-0 flex items-center justify-center text-xs text-zinc-400/75 ps-3 inset-s-0') }}
                >
                    {{ $iconLeading }}
                </div>
            @endif

            {{-- MODO MULTIPLE --}}
            <template x-if="multiple">
                <div class="flex w-full flex-wrap gap-1.5 overflow-hidden py-0">
                    <template x-for="value in (Array.isArray(selected) ? selected : [])" :key="value">
                        <span
                            class="inline-flex max-w-full items-center rounded-full bg-zinc-100 py-px pe-1.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <span
                                class="max-w-25 truncate ps-2"
                                x-text="options.find((o) => o.value == value)?.label || value"
                            ></span>
                            <button
                                type="button"
                                @click="removeTag(value, $event)"
                                class="ml-1 shrink-0 rounded-full p-0.5 hover:bg-zinc-200 focus:outline-none dark:hover:bg-zinc-600"
                            >
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 8.28a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94l-1.72-1.72z" clip-rule="evenodd" /></svg>
                            </button>
                        </span>
                    </template>
                    <template x-if="selectedIsEmpty()">
                        <span
                            class="block truncate align-middle leading-5.5 text-zinc-400 sm:leading-4.5 dark:text-white/60"
                            x-text="placeholder"
                        ></span>
                    </template>
                </div>
            </template>

            {{-- MODO SIMPLE --}}
            <template x-if="!multiple">
                <span
                    x-text="selectedLabels"
                    :class="{ 'text-zinc-400 dark:text-white/60': selectedIsEmpty() }"
                    class="block w-full truncate"
                ></span>
            </template>

            {{-- ICONOS --}}
            @if ($loading || $countOfTrailingIcons > 0)
                <div
                    class="pointer-events-none absolute inset-e-0 top-0 bottom-0 flex h-full items-center justify-end gap-x-1.5 pe-3"
                >
                    @if ($loading)
                        <flux:icon
                            name="loading"
                            :variant="$iconVariant"
                            :class="$iconClasses"
                            wire:loading
                            :wire:target="$wireTarget"
                        />
                    @endif
                    @if ($clearable)
                        <div class="pointer-events-auto"><flux:input.clearable inset="left right" :$size /></div>
                    @endif
                    @if ($kbd)
                        <span class="pointer-events-none">{{ $kbd }}</span>
                    @endif
                    @if ($expandable)
                        <div class="pointer-events-auto"><flux:input.expandable inset="left right" :$size /></div>
                    @endif
                    @if ($copyable)
                        <div class="pointer-events-auto"><flux:input.copyable inset="left right" :$size /></div>
                    @endif
                    @if ($viewable)
                        <div class="pointer-events-auto"><flux:input.viewable inset="left right" :$size /></div>
                    @endif
                    @if (is_string($iconTrailing))
                        <flux:icon
                            :icon="$iconTrailing"
                            :variant="$iconVariant"
                            class="text-zinc-400/75 transition-transform duration-200 dark:text-white/60"
                            x-bind:class="{ 'rotate-180': open }"
                        />
                    @elseif ($iconTrailing)
                        {{ $iconTrailing }}
                    @endif
                </div>
            @endif
        </button>

        {{-- DROPDOWN --}}
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
                            <flux:icon
                                name="check"
                                :variant="$iconVariant"
                                class="text-primary-600 dark:text-primary-400 h-4 w-4 shrink-0"
                            />
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>
</flux:with-field>
