@section ('icon')
    <x-header.icon icon="users" />
@endsection

@section ('header')
    <x-header.title>USUARIOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('usuarios.index') }}" wire:navigate>USUARIOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('usuario_editar')
            || auth()->user()?->hasPermisoAccion('usuario_estado')
            || auth()->user()?->hasPermisoAccion('usuario_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar usuarios" clearable>
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

        @permiso('usuario_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarUsuario">
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
                    <x-table.heading class="min-w-70">USUARIO</x-table.heading>
                    <x-table.heading class="min-w-60">ROLES</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->usuarios as $usuario)
                    <x-table.row>
                        <x-table.cell>{{ $this->usuarios->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0">
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $usuario->nombres_usu }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $usuario->correo_usu }}</p>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($usuario->roles->take(2) as $rol)
                                    <flux:badge color="sky" size="lg">
                                        {{ $rol->nombre_rol }}
                                    </flux:badge>
                                @empty
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Sin roles asignados.</span>
                                @endforelse

                                @if ($usuario->roles->count() > 2)
                                    <flux:badge color="zinc" size="lg">
                                        +{{ $usuario->roles->count() - 2 }} más
                                    </flux:badge>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $usuario->estado_usu->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$usuario->estado_usu->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $usuario->estado_usu->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $usuario->creado_en->format('d M Y, h:i a') }}</x-table.cell>
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
                                        @permiso('usuario_editar')
                                            <flux:menu.item wire:click="cargarUsuario({{ $usuario->id_usu }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('usuario_estado')
                                            <flux:menu.item
                                                wire:click="$js.cargarAlertaEstado({{ $usuario->id_usu }}, '{{ $usuario->correo_usu }}')"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('usuario_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $usuario->id_usu }}, '{{ $usuario->correo_usu }}')"
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
                        <x-table.cell colspan="6" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron usuarios.</p>
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
            Mostrando {{ $this->usuarios->firstItem() ?? 0 }} - {{ $this->usuarios->lastItem() ?? 0 }} de {{ $this->usuarios->total() }} registros
        </div>
        {{ $this->usuarios->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('usuario_crear') || auth()->user()?->hasPermisoAccion('usuario_editar'))
        <flux:modal flyout variant="floating" name="modal-usuario" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="usuarioForm.nombres"
                            :invalid="$errors->has('usuarioForm.nombres')"
                            :error="$errors->first('usuarioForm.nombres')"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Correo"
                            type="email"
                            wire:model="usuarioForm.correo"
                            :invalid="$errors->has('usuarioForm.correo')"
                            :error="$errors->first('usuarioForm.correo')"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Contraseña"
                            type="password"
                            wire:model="usuarioForm.password"
                            :invalid="$errors->has('usuarioForm.password')"
                            :error="$errors->first('usuarioForm.password')"
                            :hint="$isEdit ? 'Deja este campo vacio si no deseas cambiar la contrasena.' : null"
                            :required="! $isEdit"
                            floating
                        />

                        <x-ui.select-multiple
                            label="Roles"
                            wire:model.live="usuarioForm.roles"
                            placeholder="Seleccionar roles"
                            :invalid="$errors->has('usuarioForm.roles')"
                            :error="$errors->first('usuarioForm.roles')"
                            floating
                        >
                            @foreach ($this->rolesDisponibles as $rol)
                                <option wire:key="rol-disponible-{{ $rol->id_rol }}" value="{{ $rol->id_rol }}">
                                    {{ $rol->nombre_rol }}
                                </option>
                            @endforeach
                        </x-ui.select-multiple>
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
        this.$js.cargarAlertaEstado = (id_usuario, correo) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado del usuario "' + correo + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'usuario-cambiar-estado',
                confirmParams: { id_usuario: id_usuario },
            });
        };

        this.$js.cargarAlertaEliminar = (id_usuario, correo) => {
            this.$dispatch('alert', {
                type: 'error',
                title: '¡Atención!',
                message: '¿Está seguro de que desea eliminar el usuario "' + correo + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'usuario-eliminar',
                confirmParams: { id_usuario: id_usuario },
            });
        };
    </script>
@endscript
