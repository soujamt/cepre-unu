@section ('icon')
    <x-header.icon icon="chart-bar" />
@endsection

@section ('header')
    <x-header.title>REPORTES DE INSCRIPCIONES</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('reportes.inscripciones.index') }}" wire:navigate>
            REPORTES DE INSCRIPCIONES</x-breadcrumbs.item
        >
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Resumen operativo del proceso</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Analice el avance de inscripciones por semestre, proceso, modalidad, turno, lugar y convenio.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button variant="filled" wire:click="limpiarFiltros" class="font-normal">
                    <x-slot name="icon">
                        <x-ui.icon name="arrows-counter-clockwise" size="5" />
                    </x-slot>
                    Limpiar filtros
                </flux:button>

                @permiso ('reporte_inscripcion_exportar_excel')
                    <flux:button
                        variant="primary"
                        color="green"
                        wire:click="exportarExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportarExcel"
                        class="font-normal"
                    >
                        <x-slot name="icon">
                            <x-ui.icon name="file-xls" size="5" />
                        </x-slot>
                        Exportar Excel
                    </flux:button>
                @endpermiso
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <x-ui.select wire:model.live="idSemestre" placeholder="Filtrar por semestre">
                <option value="">Todos los semestres</option>
                @foreach ($this->semestresDisponibles as $semestre)
                    <option wire:key="reporte-semestre-{{ $semestre->id_sem }}" value="{{ $semestre->id_sem }}">
                        {{ $semestre->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="idProceso" placeholder="Filtrar por proceso">
                <option value="">Todos los procesos</option>
                @foreach ($this->procesosDisponibles as $proceso)
                    <option wire:key="reporte-proceso-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                        {{ $proceso->nombre_pro }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="idModalidad" placeholder="Filtrar por modalidad">
                <option value="">Todas las modalidades</option>
                @foreach ($this->modalidadesDisponibles as $modalidad)
                    <option wire:key="reporte-modalidad-{{ $modalidad->id_mad }}" value="{{ $modalidad->id_mad }}">
                        {{ $modalidad->nombre_mad }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="idLugarInscripcion" placeholder="Filtrar por lugar">
                <option value="">Todos los lugares</option>
                @foreach ($this->lugaresInscripcionDisponibles as $lugarInscripcion)
                    <option
                        wire:key="reporte-lugar-{{ $lugarInscripcion->id_lin }}"
                        value="{{ $lugarInscripcion->id_lin }}"
                    >
                        {{ $lugarInscripcion->nombre_lin }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="idTurno" placeholder="Filtrar por turno">
                <option value="">Todos los turnos</option>
                @foreach ($this->turnosDisponibles as $turno)
                    <option wire:key="reporte-turno-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                        {{ $turno->nombre_tur }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="estadoInscripcion" placeholder="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach ($this->estadosInscripcionDisponibles() as $estado)
                    <option wire:key="reporte-estado-{{ $estado->value }}" value="{{ $estado->value }}">
                        {{ $estado->getNombre() }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div
        class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        wire:loading.class="opacity-60"
        wire:target="idSemestre,idProceso,idModalidad,idLugarInscripcion,idTurno,estadoInscripcion,limpiarFiltros"
    >
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total inscritos</p>
            <p class="mt-2 text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumenGeneral['total'] }}</p>
            <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                {{ $this->resumenGeneral['activas'] }} activas · {{ $this->resumenGeneral['pendientes'] }} pendientes
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pagos</p>
            <p class="mt-2 text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumenGeneral['pago_pendiente'] }}</p>
            <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                {{ $this->resumenGeneral['pago_parcial'] }} con pago parcial
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Expedientes</p>
            <p class="mt-2 text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumenGeneral['docs_observados'] }}</p>
            <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                {{ $this->resumenGeneral['docs_rechazados'] }} rechazados
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Anuladas</p>
            <p class="mt-2 text-4xl font-semibold text-zinc-900 dark:text-white">{{ $this->resumenGeneral['anuladas'] }}</p>
            <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">Estado final fuera del flujo activo</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div
            class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs xl:col-span-2 dark:border-white/10 dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen</p>
                    <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Inscritos por carrera</h3>
                </div>
                <flux:badge color="zinc" size="lg">{{ $this->reportePorCarrera->count() }}</flux:badge>
            </div>

            <div class="mt-5">
                <x-table>
                    <x-table.head>
                        <x-table.row>
                            <x-table.heading>CARRERA</x-table.heading>
                            <x-table.heading class="w-20">TOTAL</x-table.heading>
                            <x-table.heading class="w-20">ACTIVAS</x-table.heading>
                            <x-table.heading class="w-24">PENDIENTES</x-table.heading>
                            <x-table.heading class="w-20">ANULADAS</x-table.heading>
                        </x-table.row>
                    </x-table.head>
                    <x-table.body>
                        @forelse ($this->reportePorCarrera as $fila)
                            <x-table.row>
                                <x-table.cell>{{ $fila['carrera'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['total'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['activas'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['pendientes'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['anuladas'] }}</x-table.cell>
                            </x-table.row>
                        @empty
                            <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                                <x-table.cell
                                    colspan="5"
                                    class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
                                >
                                    No hay datos para mostrar.
                                </x-table.cell>
                            </x-table.row>
                        @endforelse
                    </x-table.body>
                </x-table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Cobertura</p>
            <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Por convenio</h3>

            <div class="mt-5 space-y-3">
                @forelse ($this->reportePorConvenio as $fila)
                    <div
                        class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $fila['convenio'] }}</p>
                            <flux:badge color="zinc" size="lg">{{ $fila['total'] }}</flux:badge>
                        </div>
                        <div class="mt-3 grid gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <p>Cupo maximo: {{ $fila['cupo_maximo'] ?? 'No definido' }}</p>
                            <p>Cupo disponible: {{ $fila['cupo_disponible'] ?? 'No aplica' }}</p>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-400"
                    >
                        No hay convenios asociados en el filtro actual.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Distribucion</p>
                    <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Inscritos por modalidad</h3>
                </div>
                <flux:badge color="zinc" size="lg">{{ $this->reportePorModalidad->count() }}</flux:badge>
            </div>

            <div class="mt-5">
                <x-table>
                    <x-table.head>
                        <x-table.row>
                            <x-table.heading>MODALIDAD</x-table.heading>
                            <x-table.heading class="w-20">TOTAL</x-table.heading>
                            <x-table.heading class="w-28">PAGO PTE.</x-table.heading>
                            <x-table.heading class="w-28">DOCS OBS.</x-table.heading>
                        </x-table.row>
                    </x-table.head>
                    <x-table.body>
                        @forelse ($this->reportePorModalidad as $fila)
                            <x-table.row>
                                <x-table.cell>{{ $fila['modalidad'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['total'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['pago_pendiente'] }}</x-table.cell>
                                <x-table.cell>{{ $fila['docs_observados'] }}</x-table.cell>
                            </x-table.row>
                        @empty
                            <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                                <x-table.cell
                                    colspan="4"
                                    class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
                                >
                                    No hay datos para mostrar.
                                </x-table.cell>
                            </x-table.row>
                        @endforelse
                    </x-table.body>
                </x-table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Lectura rapida</p>
                    <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Indicadores del filtro</h3>
                </div>
                <flux:badge color="zinc" size="lg">{{ $this->inscripcionesFiltradas->count() }}</flux:badge>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <flux:callout color="green" icon="check-badge">
                    <div class="space-y-1">
                        <p class="text-sm font-medium">Activas</p>
                        <p class="text-2xl font-semibold">{{ $this->resumenGeneral['activas'] }}</p>
                    </div>
                </flux:callout>

                <flux:callout color="amber" icon="clock">
                    <div class="space-y-1">
                        <p class="text-sm font-medium">Pendientes</p>
                        <p class="text-2xl font-semibold">{{ $this->resumenGeneral['pendientes'] }}</p>
                    </div>
                </flux:callout>

                <flux:callout color="sky" icon="document-magnifying-glass">
                    <div class="space-y-1">
                        <p class="text-sm font-medium">Docs observados</p>
                        <p class="text-2xl font-semibold">{{ $this->resumenGeneral['docs_observados'] }}</p>
                    </div>
                </flux:callout>

                <flux:callout color="red" icon="x-circle">
                    <div class="space-y-1">
                        <p class="text-sm font-medium">Docs rechazados</p>
                        <p class="text-2xl font-semibold">{{ $this->resumenGeneral['docs_rechazados'] }}</p>
                    </div>
                </flux:callout>
            </div>
        </div>
    </div>
</div>
