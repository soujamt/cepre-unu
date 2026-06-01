@section ('icon')
    <x-header.icon icon="student" />
@endsection

@section ('header')
    <x-header.title>COLEGIOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('mantenedor-colegios.index') }}" wire:navigate>COLEGIOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('colegio_editar')
            || auth()->user()?->hasPermisoAccion('colegio_estado')
            || auth()->user()?->hasPermisoAccion('colegio_eliminar');
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar colegios" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-56">
            <x-ui.select wire:model.live="idTipoColegioFiltro">
                <option value="">Todos los tipos</option>
                @foreach ($this->tiposColegioDisponibles as $tipoColegio)
                    <option value="{{ $tipoColegio->id_tco }}">{{ $tipoColegio->nombre_tco }}</option>
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
                    <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                        <div
                            class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                        >
                            <x-ui.icon :name="$estado->getIcon()" size="4" class="text-{{ $estado->getColor() }}-500" />
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

        @permiso('colegio_crear')
            <flux:button class="w-full shrink-0 font-normal sm:w-auto" variant="primary" wire:click="cargarColegio">
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
                    <x-table.heading class="min-w-24">CODIGO</x-table.heading>
                    <x-table.heading class="min-w-24">MODULAR</x-table.heading>
                    <x-table.heading class="min-w-56">NOMBRE</x-table.heading>
                    <x-table.heading class="min-w-40">TIPO</x-table.heading>
                    <x-table.heading class="min-w-56">UBIGEO</x-table.heading>
                    <x-table.heading class="min-w-40">DIRECCION</x-table.heading>
                    <x-table.heading class="w-20">USO</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->colegios as $colegio)
                    <x-table.row>
                        <x-table.cell class="font-medium text-zinc-800 dark:text-zinc-100">{{ $colegio->id_col }}</x-table.cell>
                        <x-table.cell>{{ $colegio->codigo_modular_col ?: 'SIN CODIGO' }}</x-table.cell>
                        <x-table.cell class="whitespace-normal font-medium text-zinc-800 dark:text-zinc-100">{{ $colegio->nombre_col }}</x-table.cell>
                        <x-table.cell>{{ $colegio->tipoColegio?->nombre_tco ?? 'SIN TIPO' }}</x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            @if ($colegio->ubigeo)
                                {{ $colegio->ubigeo->nombre_departamento_ubi }} / {{ $colegio->ubigeo->nombre_provincia_ubi }} / {{ $colegio->ubigeo->nombre_distrito_ubi }}
                            @else
                                SIN UBIGEO
                            @endif
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">{{ $colegio->direccion_col ?: 'SIN DIRECCION' }}</x-table.cell>
                        <x-table.cell>{{ $colegio->inscripciones_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $colegio->estado_col->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$colegio->estado_col->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $colegio->estado_col->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $colegio->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('colegio_editar')
                                            <flux:menu.item wire:click="cargarColegio('{{ $colegio->id_col }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('colegio_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado('{{ $colegio->id_col }}', '{{ $colegio->nombre_col }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('colegio_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar('{{ $colegio->id_col }}', '{{ $colegio->nombre_col }}')"
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
                        <x-table.cell colspan="10" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="student" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron colegios registrados.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, idTipoColegioFiltro, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idTipoColegioFiltro, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->colegios->firstItem() ?? 0 }} - {{ $this->colegios->lastItem() ?? 0 }} de {{ $this->colegios->total() }} registros
        </div>
        {{ $this->colegios->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('colegio_crear') || auth()->user()?->hasPermisoAccion('colegio_editar'))
        <flux:modal flyout variant="floating" name="modal-colegio" class="px-0! py-5! md:w-xl!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="grid gap-5 px-5 md:grid-cols-2">
                        <x-ui.input
                            label="Codigo"
                            type="text"
                            wire:model="colegioForm.id"
                            :invalid="$errors->has('colegioForm.id')"
                            :error="$errors->first('colegioForm.id')"
                            :disabled="$isEdit"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Codigo modular"
                            type="text"
                            wire:model="colegioForm.codigo_modular"
                            :invalid="$errors->has('colegioForm.codigo_modular')"
                            :error="$errors->first('colegioForm.codigo_modular')"
                            floating
                        />

                        <div class="md:col-span-2">
                            <x-ui.input
                                label="Nombre"
                                type="text"
                                wire:model="colegioForm.nombre"
                                :invalid="$errors->has('colegioForm.nombre')"
                                :error="$errors->first('colegioForm.nombre')"
                                required
                                floating
                            />
                        </div>

                        <x-ui.select
                            label="Tipo de colegio"
                            wire:model="colegioForm.id_tipo_colegio"
                            :invalid="$errors->has('colegioForm.id_tipo_colegio')"
                            :error="$errors->first('colegioForm.id_tipo_colegio')"
                            floating
                        >
                            <option value="">Selecciona un tipo</option>
                            @foreach ($this->tiposColegioDisponibles as $tipoColegio)
                                <option value="{{ $tipoColegio->id_tco }}">{{ $tipoColegio->nombre_tco }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select-search
                            label="Ubigeo"
                            wire:model.live="colegioForm.id_ubigeo"
                            :invalid="$errors->has('colegioForm.id_ubigeo')"
                            :error="$errors->first('colegioForm.id_ubigeo')"
                            :action="route('api.ubigeo.search')"
                            :selected-label="$ubigeoLabel"
                            floating
                        />

                        <div class="md:col-span-2">
                            <x-ui.textarea
                                label="Direccion"
                                wire:model="colegioForm.direccion"
                                :invalid="$errors->has('colegioForm.direccion')"
                                :error="$errors->first('colegioForm.direccion')"
                                autoResize
                                floating
                            />
                        </div>
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
        this.$js.cargarAlertaEstado = (id_colegio, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Â¿Esta seguro de que desea cambiar el estado del colegio "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'colegio-cambiar-estado',
                confirmParams: { id_colegio: id_colegio },
            });
        };

        this.$js.cargarAlertaEliminar = (id_colegio, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Â¿Esta seguro de que desea eliminar el colegio "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'colegio-eliminar',
                confirmParams: { id_colegio: id_colegio },
            });
        };
    </script>
@endscript
