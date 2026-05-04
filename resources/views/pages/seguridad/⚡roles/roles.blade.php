@section ('icon')
    <x-header.icon icon="shield" />
@endsection

@section ('header')
    <x-header.title>ROLES</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('roles.index') }}" wire:navigate>ROLES</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('rol_editar')
            || auth()->user()?->hasPermisoAccion('rol_estado')
            || auth()->user()?->hasPermisoAccion('rol_eliminar')
            || auth()->user()?->hasPermisoAccion('rol_permisos');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar roles" clearable>
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
                        <flux:badge color="zinc" class="ms-2"> {{ count($this->estadosSeleccionados) }} </flux:badge>
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

        @permiso('rol_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarRol">
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
                    <x-table.heading class="min-w-50">NOMBRE</x-table.heading>
                    <x-table.heading class="min-w-70">DESCRIPCI&Oacute;N</x-table.heading>
                    <x-table.heading class="min-w-28">ACCIONES</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->roles as $rol)
                    <x-table.row>
                        <x-table.cell>{{ $this->roles->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $rol->nombre_rol }}</x-table.cell
                        >
                        <x-table.cell class="whitespace-normal">
                            {{ \Illuminate\Support\Str::limit($rol->descripcion_rol ?: 'Sin descripción.', 30) }}
                        </x-table.cell>
                        <x-table.cell>{{ $rol->acciones_asignadas_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $rol->estado_rol->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$rol->estado_rol->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $rol->estado_rol->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $rol->creado_en->format('d M Y, h:i a') }}</x-table.cell>
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
                                        @permiso('rol_editar')
                                            <flux:menu.item wire:click="cargarRol({{ $rol->id_rol }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('rol_permisos')
                                            <flux:menu.item href="{{ route('roles.permisos', $rol->id_rol) }}" wire:navigate>
                                                <x-slot name="icon">
                                                    <x-ui.icon name="key" size="4" class="me-2" />
                                                </x-slot>
                                                Asignar permisos
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('rol_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $rol->id_rol }}, '{{ $rol->nombre_rol }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('rol_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $rol->id_rol }}, '{{ $rol->nombre_rol }}')"
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
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron roles.</p>
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
            Mostrando {{ $this->roles->firstItem() ?? 0 }} - {{ $this->roles->lastItem() ?? 0 }} de {{ $this->roles->total() }} registros
        </div>
        {{ $this->roles->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('rol_crear') || auth()->user()?->hasPermisoAccion('rol_editar'))
        <flux:modal flyout variant="floating" name="modal-rol" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="rolForm.nombre"
                            :invalid="$errors->has('rolForm.nombre')"
                            :error="$errors->first('rolForm.nombre')"
                            :disabled="$isAdministradorEdit"
                            required
                            floating
                        />

                        <x-ui.textarea
                            label="Descripción"
                            wire:model="rolForm.descripcion"
                            :invalid="$errors->has('rolForm.descripcion')"
                            :error="$errors->first('rolForm.descripcion')"
                            autoResize
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
        this.$js.cargarAlertaEstado = (id_rol, nombre_rol) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado del rol "' + nombre_rol + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'rol-cambiar-estado',
                confirmParams: { id_rol: id_rol },
            });
        };

        this.$js.cargarAlertaEliminar = (id_rol, nombre_rol) => {
            this.$dispatch('alert', {
                type: 'error',
                title: '¡Atención!',
                message: '¿Está seguro de que desea eliminar el rol "' + nombre_rol + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'rol-eliminar',
                confirmParams: { id_rol: id_rol },
            });
        };
    </script>
@endscript
