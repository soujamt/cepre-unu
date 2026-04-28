@section ('icon')
    <x-header.icon icon="building-office" />
@endsection

@section ('header')
    <x-header.title>AULAS POR PROCESO</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('aulas.index') }}" wire:navigate>AULAS POR PROCESO</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('aula_proceso_editar')
            || auth()->user()?->hasPermisoAccion('aula_proceso_estado')
            || auth()->user()?->hasPermisoAccion('aula_proceso_eliminar');
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar aulas por proceso" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        @permiso ('aula_proceso_crear')
            <flux:button
                class="w-full shrink-0 font-normal sm:ms-auto sm:w-auto"
                variant="primary"
                href="{{ route('aulas.asignar') }}"
                wire:navigate
            >
                <x-slot name="icon">
                    <x-ui.icon name="stack-plus" size="5" class="text-white" />
                </x-slot>
                Asignar aulas
            </flux:button>
        @endpermiso
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-40">
            <x-ui.select wire:model.live="idSemestre" placeholder="Filtrar por semestre">
                <option value="">Todos los semestres</option>
                @foreach ($this->semestresDisponibles as $semestre)
                    <option wire:key="semestre-aula-proceso-{{ $semestre->id_sem }}" value="{{ $semestre->id_sem }}">
                        {{ $semestre->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-56">
            <x-ui.select wire:model.live="idProceso" wire:key="aulas-proceso-select-{{ $idSemestre !== '' ? $idSemestre : 'todos' }}" placeholder="Filtrar por proceso">
                <option value="">Todos los procesos</option>
                @foreach ($this->procesosDisponibles as $proceso)
                    <option wire:key="proceso-filtro-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <flux:dropdown>
            <flux:button class="font-normal" icon:trailing="chevron-down">
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
                            <x-ui.icon :name="$estado->getIcon()" size="4" class="text-{{ $estado->getColor() }}-500" />
                        </div>
                        {{ ucfirst(strtolower($estado->getNombre())) }}
                    </flux:menu.checkbox>
                @endforeach

                @if ($idSemestre !== '' || $idProceso !== '' || count($this->estadosSeleccionados) > 0)
                    <flux:menu.separator />
                    @if ($idSemestre !== '' || $idProceso !== '')
                        <flux:menu.item wire:click="limpiarFiltrosAcademicos" class="font-normal!">
                            <x-slot name="icon">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon name="x" size="4" />
                                </div>
                            </x-slot>
                            Limpiar filtros academicos
                        </flux:menu.item>
                    @endif

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar estados
                        </flux:menu.item>
                    @endif
                @endif
            </flux:menu>
        </flux:dropdown>
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="min-w-56">PROCESO</x-table.heading>
                    <x-table.heading class="min-w-44">AULA</x-table.heading>
                    <x-table.heading class="min-w-32">LUGAR</x-table.heading>
                    <x-table.heading class="w-24">CAPACIDAD</x-table.heading>
                    <x-table.heading class="w-24">INSCRITOS</x-table.heading>
                    <x-table.heading class="w-32">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->asignaciones as $asignacion)
                    <x-table.row wire:key="asignacion-{{ $asignacion->id_pau }}">
                        <x-table.cell>{{ $this->asignaciones->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <div class="space-y-0">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $asignacion->proceso?->nombre_pro }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $asignacion->proceso?->semestre?->nombre_sem }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="building-office" size="4" class="text-zinc-400" />
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $asignacion->aula?->nombre_aul }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $asignacion->aula?->piso_aul?->getNombre() }}
                                </span>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $asignacion->aula?->lugar_aul?->getColor() }}" size="sm">
                                {{ $asignacion->aula?->lugar_aul?->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $asignacion->capacidad_pau }}</span>
                        </x-table.cell>
                        <x-table.cell>{{ $asignacion->inscripciones_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $asignacion->estado_pau->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$asignacion->estado_pau->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $asignacion->estado_pau->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $asignacion->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell>
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('aula_proceso_editar')
                                            <flux:menu.item wire:click="cargarAsignacion({{ $asignacion->id_pau }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('aula_proceso_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $asignacion->id_pau }}, '{{ $asignacion->aula?->nombre_aul }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('aula_proceso_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $asignacion->id_pau }}, '{{ $asignacion->aula?->nombre_aul }}')"
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
                                <x-ui.icon name="building-office" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron asignaciones con los filtros aplicados.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, idSemestre, idProceso, filtroEstado, limpiarFiltrosAcademicos, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idSemestre, idProceso, filtroEstado, limpiarFiltrosAcademicos, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->asignaciones->firstItem() ?? 0 }} - {{ $this->asignaciones->lastItem() ?? 0 }} de {{ $this->asignaciones->total() }} registros
        </div>
        {{ $this->asignaciones->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('aula_proceso_editar'))
        <flux:modal flyout variant="floating" name="modal-asignacion" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">Editar aula asignada</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Capacidad"
                            type="number"
                            wire:model="aulaForm.capacidad_pau"
                            :invalid="$errors->has('aulaForm.capacidad_pau')"
                            :error="$errors->first('aulaForm.capacidad_pau')"
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
        this.$js.cargarAlertaEstado = (id_asignacion, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: '¿Esta seguro de que desea cambiar el estado del aula "' + nombre + '" en este proceso?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'asignacion-cambiar-estado',
                confirmParams: { id_asignacion: id_asignacion },
            });
        };

        this.$js.cargarAlertaEliminar = (id_asignacion, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: '¿Esta seguro de que desea eliminar la asignacion del aula "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'asignacion-eliminar',
                confirmParams: { id_asignacion: id_asignacion },
            });
        };
    </script>
@endscript
