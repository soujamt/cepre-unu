@section ('icon')
    <x-header.icon icon="clock" />
@endsection

@section ('header')
    <x-header.title>Turnos</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('turnos.index') }}" wire:navigate>Turnos</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('turno_editar')
            || auth()->user()?->hasPermisoAccion('turno_estado')
            || auth()->user()?->hasPermisoAccion('turno_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar por código o nombre" clearable>
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

        @permiso ('turno_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarTurno">
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
                    <x-table.heading class="w-24">OFERTAS</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->turnos as $turno)
                    <x-table.row wire:key="turno-{{ $turno->id_tur }}">
                        <x-table.cell>{{ $this->turnos->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <span class="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $turno->codigo_tur }}</span>
                        </x-table.cell>
                        <x-table.cell class="font-medium whitespace-normal text-zinc-800 dark:text-zinc-100">{{ $turno->nombre_tur }}</x-table.cell>
                        <x-table.cell>{{ $turno->procesos_oferta_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $turno->estado_tur->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$turno->estado_tur->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $turno->estado_tur->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2">
                            @if ($turno->creado_en)
                                <span class="block tabular-nums">{{ $turno->creado_en->format('d/m/Y') }}</span>
                                <span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $turno->creado_en->format('h:i a') }}</span>
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
                                        @permiso ('turno_editar')
                                            <flux:menu.item wire:click="cargarTurno({{ $turno->id_tur }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('turno_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $turno->id_tur }}, {{ \Illuminate\Support\Js::from($turno->nombre_tur) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('turno_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $turno->id_tur }}, {{ \Illuminate\Support\Js::from($turno->nombre_tur) }})"
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
                                <x-ui.icon name="clock" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron turnos registrados.</p>
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
            Mostrando {{ $this->turnos->firstItem() ?? 0 }} - {{ $this->turnos->lastItem() ?? 0 }} de {{ $this->turnos->total() }} registros
        </div>
        {{ $this->turnos->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('turno_crear') || auth()->user()?->hasPermisoAccion('turno_editar'))
        <flux:modal flyout variant="floating" name="modal-turno" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Código"
                            type="text"
                            wire:model="turnoForm.codigo"
                            :invalid="$errors->has('turnoForm.codigo')"
                            :error="$errors->first('turnoForm.codigo')"
                            maxlength="20"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="turnoForm.nombre"
                            :invalid="$errors->has('turnoForm.nombre')"
                            :error="$errors->first('turnoForm.nombre')"
                            required
                            floating
                        />
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
        this.$js.cargarAlertaEstado = (id_turno, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado de el turno "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'turno-cambiar-estado',
                confirmParams: { id_turno: id_turno },
            });
        };

        this.$js.cargarAlertaEliminar = (id_turno, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar el turno "' + nombre + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'turno-eliminar',
                confirmParams: { id_turno: id_turno },
            });
        };
    </script>
@endscript
