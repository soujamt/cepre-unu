@section ('icon')
    <x-header.icon icon="location-pin" />
@endsection

@section ('header')
    <x-header.title>Lugares de inscripción</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('lugares-inscripcion.index') }}" wire:navigate>Lugares de inscripción</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeEditar = auth()->user()?->hasPermisoAccion('lugar_inscripcion_editar');
        $puedeGestionarAcciones = $puedeEditar
            || auth()->user()?->hasPermisoAccion('lugar_inscripcion_estado')
            || auth()->user()?->hasPermisoAccion('lugar_inscripcion_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar por código o nombre" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-48">
            <x-ui.select wire:model.live="idSede" placeholder="Todas las sedes">
                <option value="">Todas las sedes</option>
                @foreach ($this->sedesFiltro as $sedeFiltro)
                    <option wire:key="sede-filtro-{{ $sedeFiltro->id_sed }}" value="{{ $sedeFiltro->id_sed }}">{{ $sedeFiltro->nombre_sed }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-auto">
            <flux:dropdown>
                <flux:button class="w-full font-normal" icon:trailing="chevron-down">
                    <x-slot name="icon">
                        <x-ui.icon name="filter" size="5" />
                    </x-slot>
                    Estado
                    @if (count($this->estadosSeleccionados) > 0)
                        <span class="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-index-500 px-1.5 text-[11px] leading-5 font-semibold text-white">{{ count($this->estadosSeleccionados) }}</span>
                    @endif
                </flux:button>

                <flux:menu keep-open class="dark:bg-zinc-800">
                    @foreach ($this->estados() as $estado)
                        <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                            <x-ui.icon :name="$estado->getIcon()" size="4" class="me-2 text-{{ $estado->getColor() }}-500" />
                            {{ ucfirst(strtolower($estado->getNombre())) }}
                        </flux:menu.checkbox>
                    @endforeach

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <x-ui.icon name="trash" size="4" class="me-2" />
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso ('lugar_inscripcion_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarLugar">
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nuevo
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="w-28">CÓDIGO</x-table.heading>
                    <x-table.heading>NOMBRE</x-table.heading>
                    <x-table.heading>SEDE</x-table.heading>
                    <x-table.heading>MODALIDADES</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->lugares as $lugar)
                    <x-table.row wire:key="lugar-{{ $lugar->id_lin }}">
                        <x-table.cell>{{ $this->lugares->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <span class="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $lugar->codigo_lin }}</span>
                        </x-table.cell>
                        <x-table.cell class="font-medium whitespace-normal text-zinc-800 dark:text-zinc-100">{{ $lugar->nombre_lin }}</x-table.cell>
                        <x-table.cell class="whitespace-normal">{{ $lugar->sede?->nombre_sed ?? 'SIN SEDE' }}</x-table.cell>
                        <x-table.cell class="h-auto py-2 whitespace-normal">
                            <div class="flex flex-wrap items-center gap-1">
                                @forelse ($lugar->modalidadesLugarInscripcion as $modalidadLugar)
                                    <flux:badge size="sm" color="zinc">{{ $modalidadLugar->modalidadAdmision?->nombre_mad }}</flux:badge>
                                @empty
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Sin modalidades</span>
                                @endforelse

                                @if ($puedeEditar)
                                    <button
                                        type="button"
                                        wire:click="cargarModalidadesLugar({{ $lugar->id_lin }})"
                                        class="inline-flex size-6 items-center justify-center rounded-md text-index-600 transition-colors hover:bg-index-50 dark:text-index-300 dark:hover:bg-index-500/10"
                                        title="Asignar modalidades"
                                        aria-label="Asignar modalidades"
                                    >
                                        <x-ui.icon name="pencil" size="3.5" />
                                    </button>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $lugar->estado_lin->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$lugar->estado_lin->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $lugar->estado_lin->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2">
                            @if ($lugar->creado_en)
                                <span class="block tabular-nums">{{ $lugar->creado_en->format('d/m/Y') }}</span>
                                <span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $lugar->creado_en->format('h:i a') }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('lugar_inscripcion_editar')
                                            <flux:menu.item wire:click="cargarLugar({{ $lugar->id_lin }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>

                                            <flux:menu.item wire:click="cargarModalidadesLugar({{ $lugar->id_lin }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="layers" size="4" class="me-2" />
                                                </x-slot>
                                                Modalidades
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('lugar_inscripcion_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $lugar->id_lin }}, {{ \Illuminate\Support\Js::from($lugar->nombre_lin) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('lugar_inscripcion_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $lugar->id_lin }}, {{ \Illuminate\Support\Js::from($lugar->nombre_lin) }})"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="trash" size="4" class="me-2" />
                                                </x-slot>
                                                Eliminar
                                            </flux:menu.item>
                                        @endpermiso
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                        <x-table.cell colspan="8" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="location-pin" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron lugares de inscripción registrados.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, guardarModalidades, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, guardarModalidades, cambiarEstado, eliminar"
        >
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->lugares->firstItem() ?? 0 }} - {{ $this->lugares->lastItem() ?? 0 }} de {{ $this->lugares->total() }} registros
        </div>
        {{ $this->lugares->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('lugar_inscripcion_crear') || $puedeEditar)
        <flux:modal flyout variant="floating" name="modal-lugar" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Código"
                            type="text"
                            wire:model="lugarForm.codigo"
                            :invalid="$errors->has('lugarForm.codigo')"
                            :error="$errors->first('lugarForm.codigo')"
                            maxlength="20"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="lugarForm.nombre"
                            :invalid="$errors->has('lugarForm.nombre')"
                            :error="$errors->first('lugarForm.nombre')"
                            required
                            floating
                        />

                        <x-ui.select
                            label="Sede"
                            wire:model="lugarForm.id_sede"
                            :invalid="$errors->has('lugarForm.id_sede')"
                            :error="$errors->first('lugarForm.id_sede')"
                            required
                            floating
                        >
                            <option value="">Selecciona una sede</option>
                            @foreach ($this->sedesFormulario as $sedeOpcion)
                                <option wire:key="sede-formulario-{{ $sedeOpcion->id_sed }}" value="{{ $sedeOpcion->id_sed }}">{{ $sedeOpcion->nombre_sed }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
                            </x-slot>
                            Guardar
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endif

    @if ($puedeEditar)
        <flux:modal flyout variant="floating" name="modal-lugar-modalidades" class="px-0! py-5! md:w-lg!">
            <div class="space-y-5">
                <div class="space-y-1 px-5">
                    <flux:heading size="lg">Modalidades del lugar</flux:heading>
                    <flux:text>{{ $lugarModalidadesNombre }}</flux:text>
                </div>
                <flux:separator variant="subtle" />

                <form wire:submit="guardarModalidades" class="space-y-5">
                    <div class="space-y-3 px-5">
                        <div class="flex items-center justify-end gap-3">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ count($modalidadesSeleccionadas) }}</span> seleccionadas
                            </span>
                        </div>

                        @error ('modalidadesSeleccionadas.*')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-1 gap-2">
                            @forelse ($this->modalidadesDisponibles as $modalidad)
                                <label
                                    wire:key="lugar-modalidad-{{ $modalidad->id_mad }}"
                                    class="flex min-w-0 cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 transition-colors hover:border-index-300 hover:bg-index-50/60 has-checked:border-index-300 has-checked:bg-index-50/60 dark:border-white/10 dark:hover:border-index-500/40 dark:hover:bg-index-500/10 dark:has-checked:border-index-500/40 dark:has-checked:bg-index-500/10"
                                >
                                    <input
                                        type="checkbox"
                                        wire:model.live="modalidadesSeleccionadas"
                                        value="{{ $modalidad->id_mad }}"
                                        class="size-4 shrink-0 rounded border-zinc-300 accent-index-500 dark:border-zinc-600 dark:bg-zinc-900"
                                    />
                                    <span class="min-w-0 flex-1 text-sm leading-5 font-medium text-zinc-800 dark:text-zinc-100">{{ $modalidad->nombre_mad }}</span>
                                    @if ($modalidad->estado_mad !== \App\Enums\EstadoCatalogo::Habilitado)
                                        <flux:badge size="sm" color="{{ $modalidad->estado_mad->getColor() }}">{{ $modalidad->estado_mad->getNombre() }}</flux:badge>
                                    @endif
                                    <span class="shrink-0 font-mono text-xs text-zinc-400 dark:text-zinc-500">{{ $modalidad->codigo_mad }}</span>
                                </label>
                            @empty
                                <div class="flex flex-col items-center justify-center gap-2 py-6 text-center">
                                    <x-ui.icon name="layers" size="10" class="text-zinc-400 dark:text-zinc-500" />
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay modalidades habilitadas. Regístrelas primero en Modalidades de admisión.</p>
                                </div>
                            @endforelse
                        </div>

                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Al quitar una modalidad que ya tiene ofertas configuradas, se deshabilita en el lugar en lugar de eliminarse.
                        </p>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
                            </x-slot>
                            Guardar
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endif
</div>

@script
    <script>
        this.$js.cargarAlertaEstado = (id_lugar, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado del lugar "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'lugar-cambiar-estado',
                confirmParams: { id_lugar: id_lugar },
            });
        };

        this.$js.cargarAlertaEliminar = (id_lugar, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar el lugar "' + nombre + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'lugar-eliminar',
                confirmParams: { id_lugar: id_lugar },
            });
        };
    </script>
@endscript
