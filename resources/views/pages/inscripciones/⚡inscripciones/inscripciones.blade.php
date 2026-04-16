@section ('icon')
    <x-header.icon icon="clipboard-text" />
@endsection

@section ('header')
    <x-header.title>INSCRIPCIONES</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.index') }}" wire:navigate>INSCRIPCIONES</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div
        class="grid gap-4 transition-opacity duration-200 lg:grid-cols-4"
        wire:loading.class="opacity-50"
        wire:target="buscar, idProceso, filtroEstado, porPagina, limpiarFiltroProceso, limpiarFiltroEstado"
    >
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total inscritas</p>
                    <p class="text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumen['total'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                        {{ $this->resumen['activas'] }} activas · {{ $this->resumen['pendientes'] }} pendientes
                    </p>
                </div>

                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
                >
                    <x-ui.icon name="users-three" size="6" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pago pendiente</p>
                    <p class="text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumen['pago_pendiente'] }}</p>
                </div>

                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                >
                    <x-ui.icon name="currency-circle-dollar" size="6" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Docs observados</p>
                    <p class="text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumen['docs_observados'] }}</p>
                </div>

                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300"
                >
                    <x-ui.icon name="warning" size="6" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Anuladas</p>
                    <p class="text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumen['anuladas'] }}</p>
                </div>

                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-300"
                >
                    <x-ui.icon name="x-circle" size="6" />
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
        <div class="w-full xl:w-sm">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar inscripciones" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full xl:w-md">
            <x-ui.select wire:model.live="idProceso" placeholder="Filtrar por proceso">
                <option value="">Todos los procesos</option>
                @foreach ($this->procesosDisponibles as $proceso)
                    <option wire:key="proceso-inscripcion-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full xl:w-auto">
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
                        <flux:menu.checkbox
                            wire:key="estado-{{ $estado->value }}"
                            wire:model.live="filtroEstado.{{ $estado->value }}"
                            class="font-normal"
                        >
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

                    @if ($idProceso !== '' || count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        @if ($idProceso !== '')
                            <flux:menu.item wire:click="limpiarFiltroProceso" class="font-normal!">
                                <x-slot name="icon">
                                    <div
                                        class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                    >
                                        <x-ui.icon name="x" size="4" />
                                    </div>
                                </x-slot>
                                Limpiar proceso
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

        @permiso ('inscripcion_crear')
            <flux:button
                variant="primary"
                href="{{ route('inscripciones.crear') }}"
                wire:navigate
                class="ms-auto w-full font-normal xl:w-auto"
            >
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nueva inscripcion
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="min-w-28">CODIGO</x-table.heading>
                    <x-table.heading class="min-w-56">POSTULANTE</x-table.heading>
                    <x-table.heading class="min-w-56">POSTULACION</x-table.heading>
                    <x-table.heading class="min-w-24">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-36">TRAMITE</x-table.heading>
                    <x-table.heading class="min-w-32">REGISTRO</x-table.heading>
                    <x-table.heading class="w-20"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->inscripciones as $inscripcion)
                    @php
                        $resumenPago = $this->resumenPago($inscripcion);
                        $resumenExpedientes = $this->resumenExpedientes($inscripcion);
                    @endphp
                    <x-table.row wire:key="inscripcion-{{ $inscripcion->id_ins }}">
                        <x-table.cell class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $inscripcion->codigo_ins }}
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0.5">
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ trim(implode(' ', array_filter([
                                            $inscripcion->persona?->apellido_paterno_per,
                                            $inscripcion->persona?->apellido_materno_per,
                                            $inscripcion->persona?->nombres_per,
                                        ]))) }}
                                </p>
                                <div class="space-y-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    <p>DNI: {{ $inscripcion->persona?->numero_documento_per ?? 'SIN DOCUMENTO' }}</p>
                                    <p>{{ $inscripcion->persona?->correo_electronico_per ?? 'Sin correo registrado' }}</p>
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0.5">
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA' }}
                                </p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $inscripcion->procesoOferta?->proceso?->nombre_pro ?? 'SIN PROCESO' }} · {{ $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE' }}
                                </p>
                                @php
                                    $modalidad = $inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad;
                                    $turno = $inscripcion->procesoOferta?->turno?->nombre_tur;
                                @endphp
                                @if ($modalidad || $turno)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ implode(' · ', array_filter([$modalidad, $turno])) }}
                                    </p>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $inscripcion->estado_ins->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$inscripcion->estado_ins->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $inscripcion->estado_ins->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="py-3 whitespace-normal">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-1.5">
                                    <flux:badge color="{{ $resumenPago['color'] }}" size="sm">
                                        <x-slot name="icon">
                                            <x-ui.icon name="currency-circle-dollar" size="3" class="me-0.5" />
                                        </x-slot>
                                        {{ $resumenPago['label'] }}
                                    </flux:badge>
                                    <flux:badge color="{{ $resumenExpedientes['color'] }}" size="sm">
                                        <x-slot name="icon">
                                            <x-ui.icon name="files" size="3" class="me-0.5" />
                                        </x-slot>
                                        {{ $resumenExpedientes['label'] }}
                                    </flux:badge>
                                </div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                    Docs: {{ $resumenExpedientes['detalle'] }}
                                </p>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ $inscripcion->fecha_inscripcion_ins?->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
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
                                    @permiso ('pago_crear')
                                        @if ($this->tienePagoPorValidar($inscripcion))
                                            <flux:menu.item
                                                href="{{ route('pagos.validar', ['codigo' => $inscripcion->codigo_ins]) }}"
                                                wire:navigate
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="shield-check" size="4" class="me-2" />
                                                </x-slot>
                                                Validar pago
                                            </flux:menu.item>
                                        @endif
                                    @endpermiso

                                    @if ($this->puedeVerDetalleRegistro($inscripcion))
                                        <flux:menu.item
                                            href="{{ route('inscripciones.detalle', encode($inscripcion->id_ins)) }}"
                                            wire:navigate
                                        >
                                            <x-slot name="icon">
                                                <x-ui.icon name="eye" size="4" class="me-2" />
                                            </x-slot>
                                            Ver detalle
                                        </flux:menu.item>
                                    @elseif ($this->puedeContinuarRegistro($inscripcion))
                                        <flux:menu.item
                                            href="{{ route('inscripciones.continuar', encode($inscripcion->id_ins)) }}"
                                            wire:navigate
                                        >
                                            <x-slot name="icon">
                                                <x-ui.icon name="note-pencil" size="4" class="me-2" />
                                            </x-slot>
                                            Continuar registro
                                        </flux:menu.item>
                                    @else
                                        @php ($accionEstado = $this->accionEstadoRegistro($inscripcion))
                                        <flux:menu.item disabled>
                                            <x-slot name="icon">
                                                <x-ui.icon :name="$accionEstado['icon']" size="4" class="me-2" />
                                            </x-slot>
                                            {{ $accionEstado['label'] }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                        <x-table.cell colspan="7" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="clipboard-text" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron inscripciones.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, idProceso, filtroEstado, porPagina, limpiarFiltroProceso, limpiarFiltroEstado, nextPage, previousPage, gotoPage"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idProceso, filtroEstado, porPagina, limpiarFiltroProceso, limpiarFiltroEstado, nextPage, previousPage, gotoPage"
        >
            <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                Mostrando {{ $this->inscripciones->firstItem() ?? 0 }} - {{ $this->inscripciones->lastItem() ?? 0 }} de {{ $this->inscripciones->total() }} registros
            </span>
            <x-ui.select wire:model.live="porPagina" size="sm" class="w-20">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </x-ui.select>
        </div>
        {{ $this->inscripciones->links() }}
    </div>
</div>
