@section ('icon')
    <x-header.icon icon="building-house" />
@endsection

@section ('header')
    <x-header.title>Sedes</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('sedes.index') }}" wire:navigate>Sedes</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeEditar = auth()->user()?->hasPermisoAccion('sede_editar');
        $puedeGestionarAcciones = $puedeEditar
            || auth()->user()?->hasPermisoAccion('sede_estado')
            || auth()->user()?->hasPermisoAccion('sede_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar sedes" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-auto">
            <flux:dropdown>
                <flux:button class="w-full font-normal" icon:trailing="chevron-down">
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

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <x-ui.icon name="trash" size="4" class="me-2" />
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso ('sede_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarSede">
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nueva
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="w-28">CÓDIGO</x-table.heading>
                    <x-table.heading>NOMBRE</x-table.heading>
                    <x-table.heading>UBICACIÓN</x-table.heading>
                    <x-table.heading class="w-24">CARRERAS</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->sedes as $sede)
                    <x-table.row wire:key="sede-{{ $sede->id_sed }}">
                        <x-table.cell>{{ $this->sedes->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <span class="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $sede->codigo_sed }}</span>
                        </x-table.cell>
                        <x-table.cell class="font-medium whitespace-normal text-zinc-800 dark:text-zinc-100">{{ $sede->nombre_sed }}</x-table.cell>
                        <x-table.cell class="h-auto py-2 whitespace-normal">
                            @if ($sede->ubigeo)
                                <span class="block">{{ $sede->ubigeo->nombre_distrito_ubi }}</span>
                                <span class="block text-xs text-zinc-400 dark:text-zinc-500">{{ $sede->ubigeo->nombre_provincia_ubi }} / {{ $sede->ubigeo->nombre_departamento_ubi }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">SIN UBIGEO</span>
                            @endif
                            @if ($sede->direccion_sed)
                                <span class="block text-xs text-zinc-400 dark:text-zinc-500">{{ $sede->direccion_sed }}</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            @if ($puedeEditar)
                                <button
                                    type="button"
                                    wire:click="cargarCarrerasSede({{ $sede->id_sed }})"
                                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-medium text-index-600 transition-colors hover:bg-index-50 dark:text-index-300 dark:hover:bg-index-500/10"
                                    title="Asignar carreras"
                                >
                                    {{ $sede->carreras_habilitadas_count }}
                                    <x-ui.icon name="pencil" size="3.5" />
                                </button>
                            @else
                                {{ $sede->carreras_habilitadas_count }}
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $sede->estado_sed->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$sede->estado_sed->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $sede->estado_sed->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2">
                            @if ($sede->creado_en)
                                <span class="block tabular-nums">{{ $sede->creado_en->format('d/m/Y') }}</span>
                                <span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $sede->creado_en->format('h:i a') }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('sede_editar')
                                            <flux:menu.item wire:click="cargarSede({{ $sede->id_sed }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>

                                            <flux:menu.item wire:click="cargarCarrerasSede({{ $sede->id_sed }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="education" size="4" class="me-2" />
                                                </x-slot>
                                                Carreras
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('sede_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $sede->id_sed }}, {{ \Illuminate\Support\Js::from($sede->nombre_sed) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('sede_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $sede->id_sed }}, {{ \Illuminate\Support\Js::from($sede->nombre_sed) }})"
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
                                <x-ui.icon name="building-house" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron sedes registradas.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, guardarCarreras, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, guardarCarreras, cambiarEstado, eliminar"
        >
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->sedes->firstItem() ?? 0 }} - {{ $this->sedes->lastItem() ?? 0 }} de {{ $this->sedes->total() }} registros
        </div>
        {{ $this->sedes->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('sede_crear') || $puedeEditar)
        <flux:modal flyout variant="floating" name="modal-sede" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Código"
                            type="text"
                            wire:model="sedeForm.codigo"
                            :invalid="$errors->has('sedeForm.codigo')"
                            :error="$errors->first('sedeForm.codigo')"
                            maxlength="20"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="sedeForm.nombre"
                            :invalid="$errors->has('sedeForm.nombre')"
                            :error="$errors->first('sedeForm.nombre')"
                            required
                            floating
                        />

                        <x-ui.select-search
                            label="Ubigeo"
                            wire:model.live="sedeForm.id_ubigeo"
                            :invalid="$errors->has('sedeForm.id_ubigeo')"
                            :error="$errors->first('sedeForm.id_ubigeo')"
                            :action="route('api.ubigeo.search')"
                            :selected-label="$ubigeoLabel"
                            required
                            floating
                        />

                        <x-ui.textarea
                            label="Dirección"
                            wire:model="sedeForm.direccion"
                            :invalid="$errors->has('sedeForm.direccion')"
                            :error="$errors->first('sedeForm.direccion')"
                            autoResize
                            floating
                        />
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

    @if ($puedeEditar)
        <flux:modal flyout variant="floating" name="modal-sede-carreras" class="px-0! py-5! md:w-lg!">
            <div class="space-y-5" x-data="{ filtro: '' }">
                <div class="space-y-1 px-5">
                    <flux:heading size="lg">Carreras de la sede</flux:heading>
                    <flux:text>{{ $sedeCarrerasNombre }}</flux:text>
                </div>
                <flux:separator variant="subtle" />

                <form wire:submit="guardarCarreras" class="space-y-5">
                    <div class="space-y-3 px-5">
                        <div class="flex items-center justify-between gap-3">
                            <flux:input x-model="filtro" size="sm" placeholder="Filtrar carreras" class="max-w-60">
                                <x-slot name="icon">
                                    <x-ui.icon name="search" size="4" class="text-zinc-400 dark:text-zinc-500" />
                                </x-slot>
                            </flux:input>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ count($carrerasSeleccionadas) }}</span> seleccionadas
                            </span>
                        </div>

                        @error ('carrerasSeleccionadas.*')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="grid grid-cols-1 gap-2">
                            @forelse ($this->carrerasDisponibles as $carrera)
                                <label
                                    wire:key="sede-carrera-{{ $carrera->id_car }}"
                                    x-show="filtro === '' || @js(mb_strtolower($carrera->nombre_car.' '.$carrera->codigo_car)).includes(filtro.toLowerCase())"
                                    class="flex min-w-0 cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 transition-colors hover:border-index-300 hover:bg-index-50/60 has-checked:border-index-300 has-checked:bg-index-50/60 dark:border-white/10 dark:hover:border-index-500/40 dark:hover:bg-index-500/10 dark:has-checked:border-index-500/40 dark:has-checked:bg-index-500/10"
                                >
                                    <input
                                        type="checkbox"
                                        wire:model.live="carrerasSeleccionadas"
                                        value="{{ $carrera->id_car }}"
                                        class="size-4 shrink-0 rounded border-zinc-300 accent-index-500 dark:border-zinc-600 dark:bg-zinc-900"
                                    />
                                    <span class="min-w-0 flex-1 text-sm leading-5 font-medium text-zinc-800 dark:text-zinc-100">{{ $carrera->nombre_car }}</span>
                                    @if ($carrera->estado_car !== \App\Enums\EstadoCatalogo::Habilitado)
                                        <flux:badge size="sm" color="{{ $carrera->estado_car->getColor() }}">{{ $carrera->estado_car->getNombre() }}</flux:badge>
                                    @endif
                                    <span class="shrink-0 font-mono text-xs text-zinc-400 dark:text-zinc-500">{{ $carrera->codigo_car }}</span>
                                </label>
                            @empty
                                <div class="flex flex-col items-center justify-center gap-2 py-6 text-center">
                                    <x-ui.icon name="education" size="10" class="text-zinc-400 dark:text-zinc-500" />
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay carreras habilitadas. Regístrelas primero en Carreras.</p>
                                </div>
                            @endforelse
                        </div>

                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Al quitar una carrera que ya tiene ofertas configuradas, se deshabilita en la sede en lugar de eliminarse.
                        </p>
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
        this.$js.cargarAlertaEstado = (id_sede, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado de la sede "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'sede-cambiar-estado',
                confirmParams: { id_sede: id_sede },
            });
        };

        this.$js.cargarAlertaEliminar = (id_sede, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar la sede "' + nombre + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'sede-eliminar',
                confirmParams: { id_sede: id_sede },
            });
        };
    </script>
@endscript
