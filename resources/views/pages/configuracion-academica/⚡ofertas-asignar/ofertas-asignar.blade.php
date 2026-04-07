@section ('icon')
    <x-header.icon icon="stack-plus" />
@endsection

@section ('header')
    <x-header.title>ASIGNAR OFERTAS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas.index') }}" wire:navigate>OFERTAS</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas.asignar') }}" wire:navigate>ASIGNAR</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="min-w-0">
                        <x-ui.select
                            label="Proceso"
                            wire:model.live="idProceso"
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
                                <option wire:key="turno-asignar-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                                    {{ $turno->nombre_tur }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="min-w-0">
                        <x-ui.input
                            label="Buscar oferta base"
                            type="text"
                            wire:model.live.debounce.400ms="buscar"
                            iconLeading="magnifying-glass"
                            floating
                        />
                    </div>

                    <div class="min-w-0">
                        <x-ui.select
                            label="Modalidad"
                            wire:model.live="idModalidad"
                            :invalid="$errors->has('idModalidad')"
                            :error="$errors->first('idModalidad')"
                            required
                            floating
                        >
                            <option value="">Seleccionar modalidad</option>
                            @foreach ($this->modalidadesDisponibles as $modalidad)
                                <option wire:key="modalidad-filtro-{{ $modalidad->id_mad }}" value="{{ $modalidad->id_mad }}">
                                    {{ $modalidad->nombre_mad }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.select
                            label="Lugar inscripcion"
                            wire:model.live="idLugarInscripcion"
                            :invalid="$errors->has('idLugarInscripcion')"
                            :error="$errors->first('idLugarInscripcion')"
                            required
                            floating
                        >
                            <option value="">Seleccionar lugar</option>
                            @foreach ($this->lugaresInscripcionDisponibles as $lugarInscripcion)
                                <option wire:key="lugar-filtro-{{ $lugarInscripcion->id_lin }}" value="{{ $lugarInscripcion->id_lin }}">
                                    {{ $lugarInscripcion->nombre_lin }} | {{ $lugarInscripcion->sede?->nombre_sed }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
            </div>

            @if ($this->filtrosAplicados())
                <div class="relative">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            Mostrando {{ count($this->ofertasBase) }} ofertas disponibles para seleccionar
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="seleccionarTodasLasOfertasVisibles"
                                :disabled="$this->idsOfertasSeleccionables === []"
                            >
                                Seleccionar visibles
                            </flux:button>

                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="limpiarSeleccionadasVisibles"
                                :disabled="count($seleccionadas) === 0"
                            >
                                Limpiar visibles
                            </flux:button>
                        </div>
                    </div>

                    <x-table>
                        <x-table.head>
                            <x-table.row>
                                <x-table.heading class="w-16 max-w-16">SEL.</x-table.heading>
                                <x-table.heading class="min-w-20">COD.</x-table.heading>
                                <x-table.heading class="min-w-80">OFERTA BASE</x-table.heading>
                                <x-table.heading class="min-w-24">ASIGNADA</x-table.heading>
                            </x-table.row>
                        </x-table.head>

                        <x-table.body>
                            @forelse ($this->ofertasBase as $ofertaBase)
                                @php
                                    $yaAsignada = $this->ofertaYaAsignada($ofertaBase->id_ofb);
                                @endphp
                                <x-table.row wire:key="oferta-base-{{ $ofertaBase->id_ofb }}">
                                    <x-table.cell>
                                        <input
                                            type="checkbox"
                                            value="{{ $ofertaBase->id_ofb }}"
                                            wire:model.live="seleccionadas"
                                            wire:key="oferta-checkbox-{{ $ofertaBase->id_ofb }}-{{ in_array((string) $ofertaBase->id_ofb, array_map('strval', $seleccionadas), true) ? 'on' : 'off' }}"
                                            @disabled($yaAsignada)
                                            class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                                        />
                                    </x-table.cell>
                                    <x-table.cell>{{ $ofertaBase->codigo_externo_ofb }}</x-table.cell>
                                    <x-table.cell class="whitespace-normal">
                                        <div class="space-y-1">
                                            <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->descripcionOfertaBase($ofertaBase) }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Ya asignada en el proceso: {{ $ofertaBase->asignaciones_proceso_count }}
                                            </div>
                                        </div>
                                    </x-table.cell>
                                    <x-table.cell>
                                        @if ($yaAsignada)
                                            <flux:badge color="amber" size="sm">Mismo turno</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Disponible</flux:badge>
                                        @endif
                                    </x-table.cell>
                                </x-table.row>
                            @empty
                                <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                                    <x-table.cell colspan="4" class="h-auto py-10 text-center whitespace-normal">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                            <p class="text-zinc-500 dark:text-zinc-400">No se encontraron ofertas base para esa modalidad y lugar.</p>
                                        </div>
                                    </x-table.cell>
                                </x-table.row>
                            @endforelse
                        </x-table.body>
                    </x-table>

                    <div
                        class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
                        wire:loading
                        wire:target="buscar, idProceso, idTurno, idModalidad, idLugarInscripcion, seleccionadas, guardar, seleccionarTodasLasOfertasVisibles, limpiarSeleccionadasVisibles"
                    ></div>
                    <div
                        class="absolute inset-0 flex items-center justify-center"
                        wire:loading.flex
                        wire:target="buscar, idProceso, idTurno, idModalidad, idLugarInscripcion, seleccionadas, guardar, seleccionarTodasLasOfertasVisibles, limpiarSeleccionadasVisibles"
                    >
                        <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="funnel" size="12" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-zinc-700 dark:text-zinc-200">Seleccione modalidad y lugar de inscripcion para ver las ofertas disponibles.</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Luego podra seleccionar varias ofertas en una sola carga.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
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
                            {{ $this->turnoSeleccionado?->nombre_tur ?? 'Seleccione un turno' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Seleccionadas</p>
                        <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ count($seleccionadas) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Filtros activos</p>
                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-100">
                            {{ $this->idModalidad !== '' || $this->idLugarInscripcion !== '' ? 'Si' : 'No' }}
                        </p>
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

                    <flux:button variant="ghost" class="w-full font-normal" href="{{ route('ofertas.index') }}" wire:navigate>
                        Volver a ofertas
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
