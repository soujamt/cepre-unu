@section ('icon')
    <x-header.icon icon="calendar-blank" />
@endsection

@section ('header')
    <x-header.title>SEMESTRES</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('semestres.index') }}" wire:navigate>SEMESTRES</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('semestre_editar')
            || auth()->user()?->hasPermisoAccion('semestre_estado')
            || auth()->user()?->hasPermisoAccion('semestre_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar semestres" clearable>
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

        @permiso('semestre_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarSemestre">
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
                    <x-table.heading class="min-w-34">INICIO INSCRIPCION</x-table.heading>
                    <x-table.heading class="min-w-34">FIN INSCRIPCION</x-table.heading>
                    <x-table.heading class="min-w-24">PROCESOS</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->semestres as $semestre)
                    <x-table.row>
                        <x-table.cell>{{ $this->semestres->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="font-medium text-zinc-800 dark:text-zinc-100">{{ $semestre->nombre_sem }}</x-table.cell>
                        <x-table.cell>{{ $semestre->fecha_inicio_inscripcion_sem->format('d/m/Y') }}</x-table.cell>
                        <x-table.cell>{{ $semestre->fecha_fin_inscripcion_sem->format('d/m/Y') }}</x-table.cell>
                        <x-table.cell>{{ $semestre->procesos_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $semestre->estado_sem->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$semestre->estado_sem->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $semestre->estado_sem->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $semestre->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('semestre_editar')
                                            <flux:menu.item wire:click="cargarSemestre({{ $semestre->id_sem }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('semestre_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $semestre->id_sem }}, '{{ $semestre->nombre_sem }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('semestre_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $semestre->id_sem }}, '{{ $semestre->nombre_sem }}')"
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
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron semestres.</p>
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
            Mostrando {{ $this->semestres->firstItem() ?? 0 }} - {{ $this->semestres->lastItem() ?? 0 }} de {{ $this->semestres->total() }} registros
        </div>
        {{ $this->semestres->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('semestre_crear') || auth()->user()?->hasPermisoAccion('semestre_editar'))
        <flux:modal flyout variant="floating" name="modal-semestre" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="semestreForm.nombre"
                            :invalid="$errors->has('semestreForm.nombre')"
                            :error="$errors->first('semestreForm.nombre')"
                            hint="El codigo se genera automaticamente desde el nombre."
                            required
                            floating
                        />

                        <x-ui.input
                            label="Fecha inicio de inscripcion"
                            type="date"
                            wire:model="semestreForm.fecha_inicio_inscripcion"
                            :invalid="$errors->has('semestreForm.fecha_inicio_inscripcion')"
                            :error="$errors->first('semestreForm.fecha_inicio_inscripcion')"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Fecha fin de inscripcion"
                            type="date"
                            wire:model="semestreForm.fecha_fin_inscripcion"
                            :invalid="$errors->has('semestreForm.fecha_fin_inscripcion')"
                            :error="$errors->first('semestreForm.fecha_fin_inscripcion')"
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
        this.$js.cargarAlertaEstado = (id_semestre, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: '¿Esta seguro de que desea cambiar el estado del semestre "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'semestre-cambiar-estado',
                confirmParams: { id_semestre: id_semestre },
            });
        };

        this.$js.cargarAlertaEliminar = (id_semestre, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: '¿Esta seguro de que desea eliminar el semestre "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'semestre-eliminar',
                confirmParams: { id_semestre: id_semestre },
            });
        };
    </script>
@endscript
