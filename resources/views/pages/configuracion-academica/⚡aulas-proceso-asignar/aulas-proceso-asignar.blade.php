@section ('icon')
    <x-header.icon icon="stack-plus" />
@endsection

@section ('header')
    <x-header.title>ASIGNAR AULAS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('aulas.index') }}" wire:navigate>AULAS POR PROCESO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('aulas.asignar') }}" wire:navigate>ASIGNAR</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="min-w-0">
                        <x-ui.select
                            label="Semestre"
                            wire:model.live="idSemestre"
                            floating
                        >
                            <option value="">Todos los semestres</option>
                            @foreach ($this->semestresDisponibles as $semestre)
                                <option wire:key="semestre-aula-asignar-{{ $semestre->id_sem }}" value="{{ $semestre->id_sem }}">
                                    {{ $semestre->nombre_sem }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.select
                            label="Proceso"
                            wire:model.live="idProceso"
                            wire:key="aulas-asignar-proceso-select-{{ $idSemestre !== '' ? $idSemestre : 'todos' }}"
                            :invalid="$errors->has('idProceso')"
                            :error="$errors->first('idProceso')"
                            required
                            floating
                        >
                            <option value="">Seleccionar proceso</option>
                            @foreach ($this->procesosDisponibles as $proceso)
                                <option wire:key="proceso-asignar-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                                    {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.select
                            label="Turno"
                            wire:model.live="idTurno"
                            :invalid="$errors->has('idTurno')"
                            :error="$errors->first('idTurno')"
                            required
                            floating
                        >
                            <option value="">Seleccionar turno</option>
                            @foreach ($this->turnosDisponibles as $turno)
                                <option wire:key="turno-aula-asignar-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                                    {{ $turno->nombre_tur }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.input
                            label="Buscar aula"
                            type="text"
                            wire:model.live.debounce.400ms="buscar"
                            iconLeading="magnifying-glass"
                            floating
                        />
                    </div>
                </div>
            </div>

            @if (filled($this->idProceso) && filled($this->idTurno))
                <div class="relative">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            Mostrando {{ count($this->aulas) }} aulas fisicas disponibles
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="seleccionarTodasLasAulasVisibles"
                                :disabled="$this->idsAulasSeleccionables === []"
                            >
                                Seleccionar visibles
                            </flux:button>

                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="limpiarSeleccionadasVisibles"
                                :disabled="count(array_filter($seleccionadas)) === 0"
                            >
                                Limpiar visibles
                            </flux:button>
                        </div>
                    </div>

                    <x-table>
                        <x-table.head>
                            <x-table.row>
                                <x-table.heading class="w-16 max-w-16">SEL.</x-table.heading>
                                <x-table.heading class="min-w-40">AULA</x-table.heading>
                                <x-table.heading class="min-w-32">LUGAR</x-table.heading>
                                <x-table.heading class="w-32">CAPACIDAD</x-table.heading>
                                <x-table.heading class="min-w-24">ASIGNADA</x-table.heading>
                            </x-table.row>
                        </x-table.head>

                        <x-table.body>
                            @forelse ($this->aulas as $aula)
                                @php
                                    $yaAsignada = $this->aulaYaAsignada($aula->id_aul);
                                    $isChecked = isset($seleccionadas[$aula->id_aul]) && $seleccionadas[$aula->id_aul];
                                @endphp
                                <x-table.row wire:key="aula-base-{{ $aula->id_aul }}">
                                    <x-table.cell>
                                        <input
                                            type="checkbox"
                                            wire:model.live="seleccionadas.{{ $aula->id_aul }}"
                                            @disabled($yaAsignada)
                                            class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 disabled:opacity-50"
                                        />
                                    </x-table.cell>
                                    <x-table.cell>
                                        <div class="flex items-center space-x-2">
                                            <x-ui.icon name="building-office" size="4" class="text-zinc-400" />
                                            <span>{{ $aula->nombre_aul }} ({{ $aula->piso_aul?->getNombre() }})</span>
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <flux:badge color="{{ $aula->lugar_aul?->getColor() }}" size="sm">
                                            {{ $aula->lugar_aul?->getNombre() }}
                                        </flux:badge>
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if (!$yaAsignada)
                                            <div class="w-24">
                                                <x-ui.input
                                                    type="number"
                                                    wire:model="capacidades.{{ $aula->id_aul }}"
                                                    min="1"
                                                    :disabled="!$isChecked"
                                                />
                                            </div>
                                        @else
                                            <span class="text-zinc-400">-</span>
                                        @endif
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if ($yaAsignada)
                                            <flux:badge color="amber" size="sm">Ya asignada</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Disponible</flux:badge>
                                        @endif
                                    </x-table.cell>
                                </x-table.row>
                            @empty
                                <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                                    <x-table.cell colspan="5" class="h-auto py-10 text-center whitespace-normal">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <x-ui.icon name="building-office" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                            <p class="text-zinc-500 dark:text-zinc-400">No se encontraron aulas.</p>
                                        </div>
                                    </x-table.cell>
                                </x-table.row>
                            @endforelse
                        </x-table.body>
                    </x-table>

                    <div
                        class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
                        wire:loading
                        wire:target="buscar, idSemestre, idProceso, idTurno, seleccionadas, guardar, seleccionarTodasLasAulasVisibles, limpiarSeleccionadasVisibles"
                    ></div>
                    <div
                        class="absolute inset-0 flex items-center justify-center"
                        wire:loading.flex
                        wire:target="buscar, idSemestre, idProceso, idTurno, seleccionadas, guardar, seleccionarTodasLasAulasVisibles, limpiarSeleccionadasVisibles"
                    >
                        <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="funnel" size="12" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-zinc-700 dark:text-zinc-200">Seleccione un proceso y un turno para ver las aulas disponibles.</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">La capacidad por defecto será de 40.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Proceso</p>
                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $this->procesoSeleccionado?->nombre_pro ?? 'Seleccione un proceso' }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $this->procesoSeleccionado?->semestre?->nombre_sem ?? 'Sin semestre seleccionado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Turno</p>
                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $this->turnosDisponibles->firstWhere('id_tur', (int) $idTurno)?->nombre_tur ?? 'Seleccione un turno' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Seleccionadas</p>
                        <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count(array_filter($seleccionadas)) }}</p>
                    </div>

                    @error('seleccionadas')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <flux:button variant="primary" class="w-full font-normal" wire:click="guardar">
                        <x-slot name="icon">
                            <x-ui.icon name="floppy-disk" size="5" class="text-white" />
                        </x-slot>
                        Registrar seleccionadas
                    </flux:button>

                    <flux:button variant="ghost" class="w-full font-normal" href="{{ route('aulas.index') }}" wire:navigate>
                        Volver a listado
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
