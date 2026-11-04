@section ('icon')
    <x-header.icon icon="key" />
@endsection

@section ('header')
    <x-header.title>Permisos</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('permisos.index') }}" wire:navigate>Permisos</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('permiso_editar')
            || auth()->user()?->hasPermisoAccion('permiso_estado')
            || auth()->user()?->hasPermisoAccion('permiso_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar permisos" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-auto">
            <flux:dropdown>
                <flux:button class="w-full font-normal" icon:trailing="chevron-down">
                    <x-slot name="icon">
                        <x-ui.icon name="filter" size="5" />
                    </x-slot>
                    Estado
                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:badge color="zinc" class="ms-2"> {{ count($this->estadosSeleccionados) }} </flux:badge>
                    @endif
                </flux:button>

                <flux:menu keep-open class="dark:bg-zinc-800">
                    @foreach ($this->estados() as $estado)
                        <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                            <x-ui.icon
                                    :name="$estado->getIcon()"
                                    size="4" class="me-2 text-{{ $estado->getColor() }}-500" />
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

        @permiso('permiso_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarPermiso">
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
                    <x-table.heading>NOMBRE</x-table.heading>
                    <x-table.heading>ACCIONES</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->permisos as $permiso)
                    <x-table.row>
                        <x-table.cell>{{ $this->permisos->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $permiso->nombre_permiso }}</x-table.cell
                        >
                        <x-table.cell class="whitespace-normal">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($permiso->permisoAcciones->take(3) as $permisoAccion)
                                    <flux:badge color="sky">
                                        {{ $permisoAccion->accion?->nombre_accion ?? $permisoAccion->clave_peracc }}
                                    </flux:badge>
                                @empty
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400"
                                        >Sin acciones configuradas.</span
                                    >
                                @endforelse

                                @if ($permiso->permisoAcciones->count() > 3)
                                    <flux:badge color="zinc">
                                        +{{ $permiso->permisoAcciones->count() - 3 }} m&aacute;s
                                    </flux:badge>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $permiso->estado_permiso->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$permiso->estado_permiso->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $permiso->estado_permiso->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2"><span class="block tabular-nums">{{ $permiso->creado_en->format('d/m/Y') }}</span><span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $permiso->creado_en->format('h:i a') }}</span></x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('permiso_editar')
                                            <flux:menu.item wire:click="cargarPermiso({{ $permiso->id_permiso }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('permiso_estado')
                                            <flux:menu.item
                                                wire:click="$js.cargarAlertaEstado({{ $permiso->id_permiso }}, '{{ $permiso->nombre_permiso }}')"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('permiso_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $permiso->id_permiso }}, '{{ $permiso->nombre_permiso }}')"
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
                        <x-table.cell colspan="7" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron registros.</p>
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
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->permisos->firstItem() ?? 0 }} - {{ $this->permisos->lastItem() ?? 0 }} de {{ $this->permisos->total() }} registros
        </div>
        {{ $this->permisos->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('permiso_crear') || auth()->user()?->hasPermisoAccion('permiso_editar'))
        <flux:modal flyout variant="floating" name="modal-permiso" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="permisoForm.nombre"
                            :invalid="$errors->has('permisoForm.nombre')"
                            :error="$errors->first('permisoForm.nombre')"
                            required
                            floating
                        />

                        <x-ui.select-multiple
                            label="Acciones"
                            wire:model.live="permisoForm.acciones"
                            placeholder="Seleccionar acciones"
                            :invalid="$errors->has('permisoForm.acciones')"
                            :error="$errors->first('permisoForm.acciones')"
                            required
                            floating
                        >
                            @foreach ($this->accionesDisponibles as $accion)
                                <option wire:key="accion-{{ $accion->id_acc }}" value="{{ $accion->id_acc }}">
                                    {{ $accion->nombre_accion }}
                                </option>
                            @endforeach
                        </x-ui.select-multiple>
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
        this.$js.cargarAlertaEstado = (id_permiso, nombre_permiso) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado del permiso "' + nombre_permiso + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'permiso-cambiar-estado',
                confirmParams: { id_permiso: id_permiso },
            });
        };

        this.$js.cargarAlertaEliminar = (id_permiso, nombre_permiso) => {
            this.$dispatch('alert', {
                type: 'error',
                title: '¡Atención!',
                message:
                    '¿Está seguro de que desea eliminar el permiso "' +
                    nombre_permiso +
                    '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'permiso-eliminar',
                confirmParams: { id_permiso: id_permiso },
            });
        };
    </script>
@endscript
