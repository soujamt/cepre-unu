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
        wire:target="buscar, idSemestre, idProceso, idModalidad, idLugarInscripcion, filtroEstado, filtroPago, filtroExpediente, porPagina, limpiarFiltroProceso, limpiarFiltrosAcademicos, limpiarFiltroEstado"
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

    {{-- Fila 1: Buscador + Acciones --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar inscripciones" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="flex shrink-0 gap-3">
            @permiso ('inscripcion_exportar_excel')
                <flux:button
                    variant="primary"
                    color="green"
                    wire:click="exportarExcel"
                    wire:loading.attr="disabled"
                    wire:target="exportarExcel"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="file-xls" size="5" />
                    </x-slot>
                    Exportar Excel
                </flux:button>
            @endpermiso

            @permiso ('inscripcion_crear')
                <flux:button variant="primary" href="{{ route('inscripciones.crear') }}" wire:navigate>
                    <x-slot name="icon">
                        <x-ui.icon name="plus" size="5" class="text-white" />
                    </x-slot>
                    Nueva inscripcion
                </flux:button>
            @endpermiso
        </div>
    </div>

    {{-- Fila 2: Filtros --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-40">
            <x-ui.select wire:model.live="idSemestre" placeholder="Filtrar por semestre">
                <option value="">Todos los semestres</option>
                @foreach ($this->semestresDisponibles as $semestre)
                    <option wire:key="semestre-inscripcion-{{ $semestre->id_sem }}" value="{{ $semestre->id_sem }}">
                        {{ $semestre->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-48">
            <x-ui.select wire:model.live="idProceso" placeholder="Filtrar por proceso">
                <option value="">Todos los procesos</option>
                @foreach ($this->procesosDisponibles as $proceso)
                    <option wire:key="proceso-inscripcion-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-44">
            <x-ui.select wire:model.live="idModalidad" placeholder="Filtrar por modalidad">
                <option value="">Todas las modalidades</option>
                @foreach ($this->modalidadesDisponibles as $modalidad)
                    <option wire:key="modalidad-inscripcion-{{ $modalidad->id_mad }}" value="{{ $modalidad->id_mad }}">
                        {{ $modalidad->nombre_mad }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-44">
            <x-ui.select wire:model.live="idLugarInscripcion" placeholder="Filtrar por lugar">
                <option value="">Todos los lugares</option>
                @foreach ($this->lugaresInscripcionDisponibles as $lugarInscripcion)
                    <option
                        wire:key="lugar-inscripcion-{{ $lugarInscripcion->id_lin }}"
                        value="{{ $lugarInscripcion->id_lin }}"
                    >
                        {{ $lugarInscripcion->nombre_lin }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-40">
            <x-ui.select wire:model.live="filtroPago" placeholder="Filtrar por pago">
                <option value="">Todos los pagos</option>
                @foreach (App\Enums\EstadoObligacionPago::cases() as $estadoPago)
                    <option wire:key="pago-{{ $estadoPago->value }}" value="{{ $estadoPago->value }}">
                        {{ ucfirst(strtolower($estadoPago->getNombre())) }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-44">
            <x-ui.select wire:model.live="filtroExpediente" placeholder="Filtrar por expedientes">
                <option value="">Todos los expedientes</option>
                <option value="sin_carga">Sin carga</option>
                @foreach (App\Enums\EstadoRevisionExpediente::cases() as $estadoExpediente)
                    <option wire:key="expediente-{{ $estadoExpediente->value }}" value="{{ $estadoExpediente->value }}">
                        {{ ucfirst(strtolower($estadoExpediente->getNombre())) }}
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
                    <flux:menu.checkbox
                        wire:key="estado-{{ $estado->value }}"
                        wire:model.live="filtroEstado.{{ $estado->value }}"
                        class="font-normal"
                    >
                        <div
                            class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                        >
                            <x-ui.icon :name="$estado->getIcon()" size="4" class="text-{{ $estado->getColor() }}-500" />
                        </div>
                        {{ ucfirst(strtolower($estado->getNombre())) }}
                    </flux:menu.checkbox>
                @endforeach

                @if ($idSemestre !== '' || $idProceso !== '' || $idModalidad !== '' || $idLugarInscripcion !== '' || count($this->estadosSeleccionados) > 0 || $filtroPago !== '' || $filtroExpediente !== '')
                    <flux:menu.separator />
                    @if ($idSemestre !== '' || $idProceso !== '' || $idModalidad !== '' || $idLugarInscripcion !== '')
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
                    @if ($filtroPago !== '')
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroPago" class="font-normal!">
                            <x-slot name="icon">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar pago
                        </flux:menu.item>
                    @endif
                    @if ($filtroExpediente !== '')
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroExpediente" class="font-normal!">
                            <x-slot name="icon">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar expedientes
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
                        <x-table.cell class="whitespace-normal py-2">
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
                        <x-table.cell class="whitespace-normal py-2">
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
                                            <flux:menu.separator />
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
                                    @endif

                                    @if ($this->puedeContinuarRegistro($inscripcion))
                                        @permiso ('inscripcion_crear')
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                href="{{ route('inscripciones.continuar', encode($inscripcion->id_ins)) }}"
                                                wire:navigate
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="note-pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Continuar registro
                                            </flux:menu.item>
                                        @endpermiso
                                    @endif

                                    @if ($inscripcion->estado_ins === App\Enums\EstadoInscripcion::Activo)
                                        @permiso ('inscripcion_estado')
                                            <flux:menu.separator />
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="abrirModalAnular({{ $inscripcion->id_ins }})"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="x-circle" size="4" class="me-2" />
                                                </x-slot>
                                                Anular inscripcion
                                            </flux:menu.item>
                                        @endpermiso
                                    @endif

                                    @permiso ('inscripcion_editar')
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="abrirModalAsignarAula({{ $inscripcion->id_ins }})">
                                            <x-slot name="icon">
                                                <x-ui.icon name="building-office" size="4" class="me-2" />
                                            </x-slot>
                                            {{ $inscripcion->id_proceso_aula ? 'Cambiar aula' : 'Asignar aula' }}
                                        </flux:menu.item>
                                    @endpermiso

                                    @if (! $this->puedeContinuarRegistro($inscripcion) && $inscripcion->estado_ins !== App\Enums\EstadoInscripcion::Activo)
                                        @php $accionEstado = $this->accionEstadoRegistro($inscripcion) @endphp
                                        <flux:menu.separator />
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
            wire:target="buscar, idSemestre, idProceso, idModalidad, idLugarInscripcion, filtroEstado, filtroPago, filtroExpediente, porPagina, limpiarFiltroProceso, limpiarFiltrosAcademicos, limpiarFiltroEstado, limpiarFiltroPago, limpiarFiltroExpediente, anularInscripcion, nextPage, previousPage, gotoPage, exportarExcel"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idSemestre, idProceso, idModalidad, idLugarInscripcion, filtroEstado, filtroPago, filtroExpediente, porPagina, limpiarFiltroProceso, limpiarFiltrosAcademicos, limpiarFiltroEstado, limpiarFiltroPago, limpiarFiltroExpediente, anularInscripcion, nextPage, previousPage, gotoPage, exportarExcel"
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

    <flux:modal name="modal-anular-inscripcion" class="md:w-md!">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Anular inscripcion</flux:heading>
                <flux:text class="mt-1"
                    >Esta accion cambiara el estado de la inscripcion a anulado y no se podra revertir.</flux:text
                >
            </div>

            <flux:field>
                <flux:label>Motivo de anulacion</flux:label>
                <flux:textarea
                    wire:model="motivoAnulacion"
                    placeholder="Describa el motivo de la anulacion (minimo 10 caracteres)"
                    rows="3"
                />
                <flux:error name="motivoAnulacion" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="filled" x-on:click="$flux.modal('modal-anular-inscripcion').close()">
                    Cancelar
                </flux:button>
                <flux:button
                    variant="danger"
                    wire:click="anularInscripcion"
                    wire:loading.attr="disabled"
                    wire:target="anularInscripcion"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="check-square-offset" size="4" />
                    </x-slot>
                    Confirmar anulacion
                </flux:button>
            </div>
        </div>
    </flux:modal>

    @permiso ('inscripcion_editar')
        <flux:modal flyout variant="floating" name="modal-asignar-aula" class="px-0! py-5! md:w-lg!">
            <div
                class="flex h-full flex-col gap-5"
                x-data="{
                    selected: $wire.entangle('idProcesoAulaSeleccionada'),
                    buscar: '',
                    aulas: [],
                    get filtradas() {
                        if (this.buscar.trim() === '') return this.aulas;
                        const q = this.buscar.toLowerCase();
                        return this.aulas.filter(a =>
                            a.nombre.toLowerCase().includes(q) ||
                            a.lugar.toLowerCase().includes(q) ||
                            a.piso.toLowerCase().includes(q)
                        );
                    },
                    porcentaje(a) {
                        return a.capacidad > 0 ? Math.round((a.ocupados / a.capacidad) * 100) : 0;
                    },
                    llena(a) {
                        return a.ocupados >= a.capacidad;
                    },
                    barColor(a) {
                        if (this.llena(a)) return 'bg-red-500';
                        if (this.porcentaje(a) >= 80) return 'bg-amber-500';
                        return this.selected === a.id ? 'bg-white dark:bg-zinc-900' : 'bg-emerald-500';
                    },
                    seleccionar(a) {
                        if (this.llena(a) && this.selected !== a.id) return;
                        this.selected = a.id;
                    },
                }"
                x-on:cargar-aulas-modal.window="aulas = $event.detail; buscar = '';"
            >
                {{-- Cabecera fija --}}
                <div class="shrink-0 space-y-4 px-5">
                    <div>
                        <flux:heading size="lg">Asignar aula</flux:heading>
                        <flux:subheading>Seleccione el aula. La capacidad se valida al guardar.</flux:subheading>
                    </div>
                    <flux:input x-model="buscar" placeholder="Buscar por nombre, lugar o piso..." clearable>
                        <x-slot name="icon">
                            <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                        </x-slot>
                    </flux:input>
                </div>

                <flux:separator variant="subtle" />

                {{-- Lista scrolleable --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-5">
                    <div class="space-y-2 pb-2">
                        <template x-if="filtradas.length === 0">
                            <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-8 text-center dark:border-zinc-700">
                                <x-ui.icon name="building-office" size="10" class="mx-auto mb-3 text-zinc-400 dark:text-zinc-500" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="buscar ? 'No se encontraron aulas con ese criterio.' : 'No hay aulas configuradas para este proceso.'"></p>
                            </div>
                        </template>

                        <template x-for="aula in filtradas" :key="aula.id">
                            <button
                                type="button"
                                x-on:click="seleccionar(aula)"
                                :disabled="llena(aula) && selected !== aula.id"
                                :class="{
                                    'border-zinc-900 bg-zinc-900 dark:border-zinc-100 dark:bg-zinc-100': selected === aula.id,
                                    'cursor-not-allowed border-zinc-200 bg-zinc-50/50 opacity-50 dark:border-zinc-800 dark:bg-zinc-900/50': llena(aula) && selected !== aula.id,
                                    'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-600': !llena(aula) && selected !== aula.id,
                                }"
                                class="w-full rounded-xl border p-4 text-left transition"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium" :class="selected === aula.id ? 'text-white dark:text-zinc-900' : 'text-zinc-800 dark:text-zinc-100'">
                                            <span x-text="aula.nombre"></span>
                                            <span class="ml-1 text-xs font-normal" :class="selected === aula.id ? 'text-zinc-300 dark:text-zinc-600' : 'text-zinc-500 dark:text-zinc-400'" x-text="aula.piso + ' · ' + aula.lugar"></span>
                                        </p>
                                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full" :class="selected === aula.id ? 'bg-zinc-700 dark:bg-zinc-300' : 'bg-zinc-200 dark:bg-zinc-700'">
                                            <div class="h-full rounded-full transition-all duration-300" :class="barColor(aula)" :style="'width: ' + Math.min(porcentaje(aula), 100) + '%'"></div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold" :class="selected === aula.id ? 'text-white dark:text-zinc-900' : (llena(aula) ? 'text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-200')" x-text="aula.ocupados + ' / ' + aula.capacidad"></p>
                                        <p class="text-xs" :class="selected === aula.id ? 'text-zinc-300 dark:text-zinc-600' : 'text-zinc-400 dark:text-zinc-500'" x-text="llena(aula) ? 'Llena' : (aula.capacidad - aula.ocupados) + ' libres'"></p>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                {{-- Footer fijo --}}
                <div class="shrink-0 space-y-3 px-5">
                    @error('idProcesoAulaSeleccionada')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end gap-3">
                        <flux:modal.close>
                            <flux:button variant="ghost" class="font-normal">Cancelar</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" class="font-normal" wire:click="guardarAulaInscripcion" wire:loading.attr="disabled" wire:target="guardarAulaInscripcion">
                            <x-slot name="icon">
                                <x-ui.icon name="floppy-disk" size="5" class="text-white" />
                            </x-slot>
                            Guardar
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:modal>
    @endpermiso
</div>

