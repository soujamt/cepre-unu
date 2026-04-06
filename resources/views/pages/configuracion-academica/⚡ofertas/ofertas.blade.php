@section ('icon')
    <x-header.icon icon="layers-2" />
@endsection

@section ('header')
    <x-header.title>OFERTAS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas.index') }}" wire:navigate>OFERTAS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('oferta_editar')
            || auth()->user()?->hasPermisoAccion('oferta_estado')
            || auth()->user()?->hasPermisoAccion('oferta_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar ofertas" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full lg:w-auto">
            <flux:dropdown>
                <flux:button class="w-full font-normal" icon:trailing="chevron-down">
                    <x-slot name="icon">
                        <x-ui.icon name="funnel" size="5" />
                    </x-slot>
                    Estado
                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:badge color="zinc" class="ms-2">{{ count($this->estadosSeleccionados) }}</flux:badge>
                    @endif
                </flux:button>

                <flux:menu keep-open class="dark:bg-zinc-800">
                    @foreach ($this->estados() as $estado)
                        <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                            <div
                                class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                            >
                                <x-ui.icon
                                    :name="$estado->getIcon()"
                                    size="4"
                                    class="text-{{ $estado->getColor() }}-500"
                                />
                            </div>
                            {{ ucfirst(strtolower($estado->getNombre())) }}
                        </flux:menu.checkbox>
                    @endforeach

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso('oferta_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" href="{{ route('ofertas.asignar') }}" wire:navigate>
                <x-slot name="icon">
                    <x-ui.icon name="stack-plus" size="5" class="text-white" />
                </x-slot>
                Asignar ofertas
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="min-w-48">PROCESO</x-table.heading>
                    <x-table.heading class="min-w-80">OFERTA BASE</x-table.heading>
                    <x-table.heading class="min-w-28">TURNO</x-table.heading>
                    <x-table.heading class="min-w-24">POSTULANTES</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->ofertas as $oferta)
                    <x-table.row>
                        <x-table.cell>{{ $this->ofertas->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $oferta->proceso?->nombre_pro }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $oferta->proceso?->semestre?->nombre_sem }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $this->descripcionOfertaBase($oferta->ofertaBase) }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Codigo externo: {{ $oferta->ofertaBase?->codigo_externo_ofb ?? 'N/A' }}
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ $oferta->turno?->nombre_tur }}</x-table.cell>
                        <x-table.cell>{{ $oferta->inscripciones_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $oferta->estado_pof->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$oferta->estado_pof->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $oferta->estado_pof->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $oferta->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('oferta_editar')
                                            <flux:menu.item wire:click="cargarOferta({{ $oferta->id_pof }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('oferta_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $oferta->id_pof }}, '{{ $oferta->proceso?->nombre_pro }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('oferta_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $oferta->id_pof }}, '{{ $oferta->proceso?->nombre_pro }}')"
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
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron ofertas.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->ofertas->firstItem() ?? 0 }} - {{ $this->ofertas->lastItem() ?? 0 }} de {{ $this->ofertas->total() }} registros
        </div>
        {{ $this->ofertas->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('oferta_editar'))
        <flux:modal flyout variant="floating" name="modal-oferta" class="px-0! py-5! md:w-xl!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.select
                            label="Proceso"
                            wire:model.live="ofertaForm.id_proceso"
                            :invalid="$errors->has('ofertaForm.id_proceso')"
                            :error="$errors->first('ofertaForm.id_proceso')"
                            required
                            floating
                        >
                            <option value="">Seleccionar proceso</option>
                            @foreach ($this->procesosDisponibles as $proceso)
                                <option wire:key="proceso-disponible-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                                    {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Oferta base"
                            wire:model.live="ofertaForm.id_oferta_base"
                            :invalid="$errors->has('ofertaForm.id_oferta_base')"
                            :error="$errors->first('ofertaForm.id_oferta_base')"
                            required
                            floating
                        >
                            <option value="">Seleccionar oferta base</option>
                            @foreach ($this->ofertasBaseDisponibles as $ofertaBase)
                                <option wire:key="oferta-base-disponible-{{ $ofertaBase->id_ofb }}" value="{{ $ofertaBase->id_ofb }}">
                                    {{ $this->descripcionOfertaBase($ofertaBase) }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Turno"
                            wire:model.live="ofertaForm.id_turno"
                            :invalid="$errors->has('ofertaForm.id_turno')"
                            :error="$errors->first('ofertaForm.id_turno')"
                            required
                            floating
                        >
                            <option value="">Seleccionar turno</option>
                            @foreach ($this->turnosDisponibles as $turno)
                                <option wire:key="turno-disponible-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                                    {{ $turno->nombre_tur }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="floppy-disk" size="5" class="text-white" />
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
        this.$js.cargarAlertaEstado = (id_oferta, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Esta seguro de que desea cambiar el estado de la oferta del proceso "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-cambiar-estado',
                confirmParams: { id_oferta: id_oferta },
            });
        };

        this.$js.cargarAlertaEliminar = (id_oferta, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Esta seguro de que desea eliminar la oferta del proceso "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-eliminar',
                confirmParams: { id_oferta: id_oferta },
            });
        };
    </script>
@endscript
