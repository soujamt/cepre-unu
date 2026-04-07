@section ('icon')
    <x-header.icon icon="files" />
@endsection

@section ('header')
    <x-header.title>REQUISITOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('requisitos.index') }}" wire:navigate>REQUISITOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('requisito_expediente_editar')
            || auth()->user()?->hasPermisoAccion('requisito_expediente_estado')
            || auth()->user()?->hasPermisoAccion('requisito_expediente_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar requisitos" clearable>
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
                            <div class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black">
                                <x-ui.icon :name="$estado->getIcon()" size="4" class="text-{{ $estado->getColor() }}-500" />
                            </div>
                            {{ ucfirst(strtolower($estado->getNombre())) }}
                        </flux:menu.checkbox>
                    @endforeach

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <div class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black">
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso('requisito_expediente_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarRequisito">
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
                    <x-table.heading class="min-w-44">PROCESO</x-table.heading>
                    <x-table.heading class="min-w-40">TIPO DE EXPEDIENTE</x-table.heading>
                    <x-table.heading class="min-w-24">OBLIGATORIO</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->requisitos as $requisito)
                    <x-table.row>
                        <x-table.cell>{{ $this->requisitos->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $requisito->proceso?->nombre_pro }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $requisito->proceso?->semestre?->nombre_sem }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $requisito->tipoExpediente?->nombre_tex }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $requisito->tipoExpediente?->codigo_tex }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge :color="$requisito->obligatorio_rex ? 'emerald' : 'zinc'" size="lg">
                                {{ $requisito->obligatorio_rex ? 'SI' : 'NO' }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $requisito->estado_rex->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$requisito->estado_rex->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $requisito->estado_rex->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $requisito->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('requisito_expediente_editar')
                                            <flux:menu.item wire:click="cargarRequisito({{ $requisito->id_rex }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('requisito_expediente_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $requisito->id_rex }}, '{{ $requisito->tipoExpediente?->nombre_tex }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('requisito_expediente_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $requisito->id_rex }}, '{{ $requisito->tipoExpediente?->nombre_tex }}')"
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
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron requisitos.</p>
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
            Mostrando {{ $this->requisitos->firstItem() ?? 0 }} - {{ $this->requisitos->lastItem() ?? 0 }} de {{ $this->requisitos->total() }} registros
        </div>
        {{ $this->requisitos->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('requisito_expediente_crear') || auth()->user()?->hasPermisoAccion('requisito_expediente_editar'))
        <flux:modal flyout variant="floating" name="modal-requisito" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.select
                            label="Proceso"
                            wire:model="requisitoForm.id_proceso"
                            :invalid="$errors->has('requisitoForm.id_proceso')"
                            :error="$errors->first('requisitoForm.id_proceso')"
                            required
                            floating
                        >
                            <option value="">Seleccionar proceso</option>
                            @foreach ($this->procesosDisponibles as $proceso)
                                <option wire:key="requisito-proceso-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                                    {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Tipo de expediente"
                            wire:model="requisitoForm.id_tipo_expediente"
                            :invalid="$errors->has('requisitoForm.id_tipo_expediente')"
                            :error="$errors->first('requisitoForm.id_tipo_expediente')"
                            required
                            floating
                        >
                            <option value="">Seleccionar tipo de expediente</option>
                            @foreach ($this->tiposExpedienteDisponibles as $tipoExpediente)
                                <option wire:key="requisito-tipo-expediente-{{ $tipoExpediente->id_tex }}" value="{{ $tipoExpediente->id_tex }}">
                                    {{ $tipoExpediente->nombre_tex }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            <input
                                type="checkbox"
                                wire:model.live="requisitoForm.obligatorio"
                                class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-900"
                            />
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Requisito obligatorio</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Si esta opcion esta activa, el expediente debe presentarse en la inscripcion.
                                </p>
                            </div>
                        </label>
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
        this.$js.cargarAlertaEstado = (id_requisito, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Â¿Esta seguro de que desea cambiar el estado del requisito "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'requisito-cambiar-estado',
                confirmParams: { id_requisito: id_requisito },
            });
        };

        this.$js.cargarAlertaEliminar = (id_requisito, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Â¿Esta seguro de que desea eliminar el requisito "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'requisito-eliminar',
                confirmParams: { id_requisito: id_requisito },
            });
        };
    </script>
@endscript
