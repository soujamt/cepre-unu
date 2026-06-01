@section ('icon')
    <x-header.icon icon="buildings" />
@endsection

@section ('header')
    <x-header.title>AULAS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('mantenedor-aulas.index') }}" wire:navigate>AULAS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('aula_editar')
            || auth()->user()?->hasPermisoAccion('aula_estado')
            || auth()->user()?->hasPermisoAccion('aula_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar aulas" clearable>
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

        @permiso ('aula_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarAula">
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nueva
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="min-w-40">NOMBRE</x-table.heading>
                    <x-table.heading class="min-w-28">PISO</x-table.heading>
                    <x-table.heading class="min-w-28">LUGAR</x-table.heading>
                    <x-table.heading class="w-20">ORDEN</x-table.heading>
                    <x-table.heading class="w-24">USO</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->aulas as $aula)
                    <x-table.row>
                        <x-table.cell>{{ $this->aulas->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell
                            class="font-medium text-zinc-800 dark:text-zinc-100"
                        >
                            {{ $aula->nombre_aul }}</x-table.cell
                        >
                        <x-table.cell>{{ $aula->piso_aul->getNombre() }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $aula->lugar_aul->getColor() }}" size="sm">
                                {{ $aula->lugar_aul->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $aula->orden_aul }}</x-table.cell>
                        <x-table.cell>{{ $aula->proceso_aulas_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $aula->estado_aul->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$aula->estado_aul->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $aula->estado_aul->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $aula->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button
                                        icon:trailing="chevron-down"
                                        size="sm"
                                        variant="filled"
                                        class="font-normal"
                                    >
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('aula_editar')
                                            <flux:menu.item wire:click="cargarAula({{ $aula->id_aul }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('aula_estado')
                                            <flux:menu.item
                                                wire:click="$js.cargarAlertaEstado({{ $aula->id_aul }}, '{{ $aula->nombre_aul }}')"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('aula_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $aula->id_aul }}, '{{ $aula->nombre_aul }}')"
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
                        <x-table.cell colspan="9" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="buildings" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron aulas registradas.</p>
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
            Mostrando {{ $this->aulas->firstItem() ?? 0 }} - {{ $this->aulas->lastItem() ?? 0 }} de {{ $this->aulas->total() }} registros
        </div>
        {{ $this->aulas->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('aula_crear') || auth()->user()?->hasPermisoAccion('aula_editar'))
        <flux:modal flyout variant="floating" name="modal-aula" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="aulaForm.nombre"
                            :invalid="$errors->has('aulaForm.nombre')"
                            :error="$errors->first('aulaForm.nombre')"
                            required
                            floating
                        />

                        <x-ui.select
                            label="Piso"
                            wire:model="aulaForm.piso"
                            :invalid="$errors->has('aulaForm.piso')"
                            :error="$errors->first('aulaForm.piso')"
                            required
                            floating
                        >
                            <option value="">Selecciona un piso</option>
                            @foreach ($this->pisos() as $piso)
                                <option value="{{ $piso->value }}">{{ $piso->getNombre() }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Lugar"
                            wire:model="aulaForm.lugar"
                            :invalid="$errors->has('aulaForm.lugar')"
                            :error="$errors->first('aulaForm.lugar')"
                            required
                            floating
                        >
                            <option value="">Selecciona un lugar</option>
                            @foreach ($this->lugares() as $lugar)
                                <option value="{{ $lugar->value }}">{{ $lugar->getNombre() }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.input
                            label="Orden"
                            type="number"
                            wire:model="aulaForm.orden"
                            :invalid="$errors->has('aulaForm.orden')"
                            :error="$errors->first('aulaForm.orden')"
                            min="1"
                            required
                            floating
                        />
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
        this.$js.cargarAlertaEstado = (id_aula, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Â¿Esta seguro de que desea cambiar el estado del aula "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'aula-cambiar-estado',
                confirmParams: { id_aula: id_aula },
            });
        };

        this.$js.cargarAlertaEliminar = (id_aula, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Â¿Esta seguro de que desea eliminar el aula "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'aula-eliminar',
                confirmParams: { id_aula: id_aula },
            });
        };
    </script>
@endscript
