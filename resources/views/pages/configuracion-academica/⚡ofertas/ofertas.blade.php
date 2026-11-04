@section ('icon')
    <x-header.icon icon="layers-alt" />
@endsection

@section ('header')
    <x-header.title>Ofertas</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas.index') }}" wire:navigate>Ofertas</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('oferta_editar')
            || auth()->user()?->hasPermisoAccion('oferta_estado')
            || auth()->user()?->hasPermisoAccion('oferta_eliminar');
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar ofertas" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        @permiso ('oferta_crear')
            <flux:button
                class="w-full shrink-0 font-normal sm:ms-auto sm:w-auto"
                variant="primary"
                href="{{ route('ofertas.asignar') }}"
                wire:navigate
            >
                <x-slot name="icon">
                    <x-ui.icon name="layers-plus-alt" size="5" class="text-white" />
                </x-slot>
                Asignar ofertas
            </flux:button>
        @endpermiso
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-40">
            <x-ui.select wire:model.live="idSemestre" placeholder="Filtrar por semestre">
                <option value="">Todos los semestres</option>
                @foreach ($this->semestresDisponibles as $semestre)
                    <option wire:key="semestre-oferta-{{ $semestre->id_sem }}" value="{{ $semestre->id_sem }}">
                        {{ $semestre->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-52">
            <x-ui.select wire:model.live="idProceso" wire:key="ofertas-proceso-select-{{ $idSemestre !== '' ? $idSemestre : 'todos' }}" placeholder="Filtrar por proceso">
                <option value="">Todos los procesos</option>
                @foreach ($this->procesosDisponibles as $proceso)
                    <option wire:key="proceso-oferta-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-40">
            <x-ui.select wire:model.live="idTurno" placeholder="Filtrar por turno">
                <option value="">Todos los turnos</option>
                @foreach ($this->turnosDisponibles as $turno)
                    <option wire:key="turno-oferta-filtro-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                        {{ $turno->nombre_tur }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>

        <flux:dropdown>
            <flux:button class="font-normal" icon:trailing="chevron-down">
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

                @if ($idSemestre !== '' || $idProceso !== '' || $idTurno !== '' || count($this->estadosSeleccionados) > 0)
                    <flux:menu.separator />
                    @if ($idSemestre !== '' || $idProceso !== '' || $idTurno !== '')
                        <flux:menu.item wire:click="limpiarFiltrosAcademicos" class="font-normal!">
                            <x-slot name="icon">
                                <x-ui.icon name="x" size="4" class="me-2" />
                            </x-slot>
                            Limpiar filtros académicos
                        </flux:menu.item>
                    @endif
                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <x-ui.icon name="trash" size="4" class="me-2" />
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
                    <x-table.heading>PROCESO</x-table.heading>
                    <x-table.heading>OFERTA BASE</x-table.heading>
                    <x-table.heading>TURNO</x-table.heading>
                    <x-table.heading>POSTULANTES</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->ofertas as $oferta)
                    @php
                        $resumenOfertaBase = $this->resumenOfertaBase($oferta->ofertaBase);
                    @endphp
                    <x-table.row>
                        <x-table.cell>{{ $this->ofertas->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <div class="space-y-0">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $oferta->proceso?->nombre_pro }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $oferta->proceso?->semestre?->nombre_sem }}
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <div class="space-y-0.5 py-1.5">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $resumenOfertaBase['carrera'] }}
                                </div>
                                <div
                                    class="flex flex-wrap items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                                >
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-white/6">
                                        {{ $resumenOfertaBase['modalidad'] }}
                                    </span>
                                    <span>{{ $resumenOfertaBase['sede'] }}</span>
                                    <span class="text-zinc-300 dark:text-zinc-600">•</span>
                                    <span>{{ $resumenOfertaBase['lugar'] }}</span>
                                </div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    Codigo externo: {{ $resumenOfertaBase['codigo_externo'] }}
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ $oferta->turno?->nombre_tur }}</x-table.cell>
                        <x-table.cell>{{ $oferta->inscripciones_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $oferta->estado_pof->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$oferta->estado_pof->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $oferta->estado_pof->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2"><span class="block tabular-nums">{{ $oferta->creado_en->format('d/m/Y') }}</span><span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $oferta->creado_en->format('h:i a') }}</span></x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('oferta_editar')
                                            <flux:menu.item wire:click="cargarOferta({{ $oferta->id_pof }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('oferta_estado')
                                            <flux:menu.item
                                                wire:click="$js.cargarAlertaEstado({{ $oferta->id_pof }}, '{{ $oferta->proceso?->nombre_pro }}')"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('oferta_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $oferta->id_pof }}, '{{ $oferta->proceso?->nombre_pro }}')"
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
                        <x-table.cell colspan="8" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron ofertas.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, idSemestre, idProceso, idTurno, filtroEstado, porPagina, limpiarFiltrosAcademicos, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idSemestre, idProceso, idTurno, filtroEstado, porPagina, limpiarFiltrosAcademicos, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->ofertas->firstItem() ?? 0 }} - {{ $this->ofertas->lastItem() ?? 0 }} de {{ $this->ofertas->total() }} registros
        </div>

        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <span>Mostrar</span>
            <x-ui.select wire:model.live="porPagina" size="sm" class="w-20">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </x-ui.select>
        </div>

        {{ $this->ofertas->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('oferta_editar'))
        <flux:modal flyout variant="floating" name="modal-oferta" class="px-0! py-5! md:w-xl!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.select
                            label="Proceso"
                            wire:model.live="ofertaForm.id_proceso"
                            :invalid="$errors->has('ofertaForm.id_proceso')"
                            :error="$errors->first('ofertaForm.id_proceso')"
                            required
                            floating
                        >
                            <option value="">Seleccionar proceso</option>
                            @foreach ($this->procesosDisponibles as $proceso)
                                <option
                                    wire:key="proceso-disponible-{{ $proceso->id_pro }}"
                                    value="{{ $proceso->id_pro }}"
                                >
                                    {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Oferta base"
                            wire:model.live="ofertaForm.id_oferta_base"
                            :invalid="$errors->has('ofertaForm.id_oferta_base')"
                            :error="$errors->first('ofertaForm.id_oferta_base')"
                            required
                            floating
                        >
                            <option value="">Seleccionar oferta base</option>
                            @foreach ($this->ofertasBaseDisponibles as $ofertaBase)
                                <option
                                    wire:key="oferta-base-disponible-{{ $ofertaBase->id_ofb }}"
                                    value="{{ $ofertaBase->id_ofb }}"
                                >
                                    {{ $this->descripcionOfertaBase($ofertaBase) }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Turno"
                            wire:model.live="ofertaForm.id_turno"
                            :invalid="$errors->has('ofertaForm.id_turno')"
                            :error="$errors->first('ofertaForm.id_turno')"
                            required
                            floating
                        >
                            <option value="">Seleccionar turno</option>
                            @foreach ($this->turnosDisponibles as $turno)
                                <option wire:key="turno-disponible-{{ $turno->id_tur }}" value="{{ $turno->id_tur }}">
                                    {{ $turno->nombre_tur }}
                                </option>
                            @endforeach
                        </x-ui.select>
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
        this.$js.cargarAlertaEstado = (id_oferta, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado de la oferta del proceso "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-cambiar-estado',
                confirmParams: { id_oferta: id_oferta },
            });
        };

        this.$js.cargarAlertaEliminar = (id_oferta, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message:
                    '¿Está seguro de que desea eliminar la oferta del proceso "' +
                    nombre +
                    '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-eliminar',
                confirmParams: { id_oferta: id_oferta },
            });
        };
    </script>
@endscript
