@section ('icon')
    <x-header.icon icon="layers-alt" />
@endsection

@section ('header')
    <x-header.title>Oferta del proceso</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas.index') }}" wire:navigate>Oferta del proceso</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeEditar = auth()->user()?->hasPermisoAccion('oferta_editar') ?? false;
        $proceso = $this->procesoSeleccionado;
    @endphp

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-5">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="max-w-sm">
                    <x-ui.select
                        label="Proceso"
                        wire:model.live="idProceso"
                        :invalid="$errors->has('idProceso')"
                        :error="$errors->first('idProceso')"
                        required
                        floating
                    >
                        <option value="">Seleccionar proceso</option>
                        @foreach ($this->procesosDisponibles as $procesoOpcion)
                            <option wire:key="proceso-oferta-{{ $procesoOpcion->id_pro }}" value="{{ $procesoOpcion->id_pro }}">
                                {{ $procesoOpcion->nombre_pro }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            @if ($proceso)
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Modalidades</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Se asigna sola según el año de egreso: hasta {{ $proceso->anioEgresoMaximoOrdinario() }} es egresado; desde {{ $proceso->anioEgresoMaximoOrdinario() + 1 }}, estudiante en colegio.
                        </p>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @forelse ($this->modalidades as $modalidad)
                            <label
                                wire:key="modalidad-{{ $modalidad->id_mad }}"
                                class="flex min-w-0 cursor-pointer items-start gap-3 rounded-lg border border-zinc-200/80 p-3 has-[:checked]:border-zinc-400 dark:border-white/10 dark:has-[:checked]:border-white/30"
                            >
                                <input
                                    type="checkbox"
                                    wire:model="modalidadesSeleccionadas.{{ $modalidad->id_mad }}"
                                    @disabled(! $puedeEditar)
                                    class="mt-0.5 size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                                />
                                <span class="min-w-0 space-y-1">
                                    <span class="block text-sm font-medium break-words text-zinc-800 dark:text-zinc-100">{{ $modalidad->nombre_mad }}</span>
                                    <flux:badge color="{{ $modalidad->condicion_egreso_mad?->getColor() }}" size="sm">
                                        {{ $modalidad->condicion_egreso_mad?->getNombre() }}
                                    </flux:badge>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                No hay modalidades habilitadas con "Aplica a" definido. Regístrelas en Mantenedores › Modalidades.
                            </p>
                        @endforelse
                    </div>

                    @error('modalidadesSeleccionadas')
                        <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @forelse ($this->sedes as $sede)
                    <div
                        wire:key="sede-{{ $sede->id_sed }}"
                        class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Carreras por sede</p>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $sede->nombre_sed }}</h3>
                            </div>

                            @if ($puedeEditar)
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge color="zinc" size="sm">{{ $this->carrerasMarcadas($sede->id_sed) }} / {{ $this->carreras->count() }}</flux:badge>
                                    <flux:button variant="ghost" size="sm" wire:click="marcarTodasLasCarreras({{ $sede->id_sed }})">Marcar todas</flux:button>
                                    <flux:button variant="ghost" size="sm" wire:click="quitarTodasLasCarreras({{ $sede->id_sed }})">Quitar todas</flux:button>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($this->carreras as $carrera)
                                <label
                                    wire:key="sede-{{ $sede->id_sed }}-carrera-{{ $carrera->id_car }}"
                                    class="flex cursor-pointer items-start gap-2 rounded-lg px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-white/5"
                                >
                                    <input
                                        type="checkbox"
                                        wire:model.live="carrerasSeleccionadas.{{ $sede->id_sed }}.{{ $carrera->id_car }}"
                                        @disabled(! $puedeEditar)
                                        class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                                    />
                                    <span>{{ $carrera->nombre_car }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center text-sm text-zinc-500 shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:text-zinc-400">
                        No hay sedes habilitadas. Regístrelas en Mantenedores › Sedes.
                    </div>
                @endforelse
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="filter" size="12" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-zinc-700 dark:text-zinc-200">Seleccione un proceso para configurar su oferta.</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Proceso</p>
                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-100">{{ $proceso?->nombre_pro ?? 'Seleccione un proceso' }}</p>
                        @if ($proceso)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $proceso->fecha_inicio_pro?->format('d/m/Y h:i a') }} al {{ $proceso->fecha_fin_pro?->format('d/m/Y h:i a') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Turnos con aulas</p>
                        <div class="mt-2 space-y-2">
                            @forelse ($this->turnosConAulas as $turno)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-800 dark:text-zinc-100">{{ $turno['nombre'] }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $turno['aulas'] }} aula(s) · {{ $turno['capacidad'] }} cupos</span>
                                </div>
                            @empty
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <p class="text-sm">El proceso no tiene aulas asignadas; no se podrá inscribir en ningún turno.</p>
                                </flux:callout>
                            @endforelse
                        </div>
                        @permiso ('aula_proceso_indice')
                            <flux:button variant="ghost" size="sm" class="mt-2 font-normal" href="{{ route('aulas.index') }}" wire:navigate>
                                Ver aulas por proceso
                            </flux:button>
                        @endpermiso
                    </div>

                    @if ($puedeEditar)
                        <flux:button variant="primary" class="w-full font-normal" wire:click="guardar" :disabled="$proceso === null">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
                            </x-slot>
                            Guardar oferta
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
