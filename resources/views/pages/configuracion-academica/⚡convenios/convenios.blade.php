@section ('icon')
    <x-header.icon icon="handshake" />
@endsection

@section ('header')
    <x-header.title>CONVENIOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('convenios.index') }}" wire:navigate>CONVENIOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('convenio_editar')
            || auth()->user()?->hasPermisoAccion('convenio_estado')
            || auth()->user()?->hasPermisoAccion('convenio_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar convenios" clearable>
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

        @permiso('convenio_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarConvenio">
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
                    <x-table.heading class="min-w-36">CONVENIO</x-table.heading>
                    <x-table.heading class="min-w-50">PROCESO</x-table.heading>
                    <x-table.heading class="min-w-40">BENEFICIO</x-table.heading>
                    <x-table.heading class="min-w-30">CUPO</x-table.heading>
                    <x-table.heading class="min-w-34">VIGENCIA</x-table.heading>
                    <x-table.heading class="min-w-24">USO</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->convenios as $convenioProceso)
                    <x-table.row>
                        <x-table.cell>{{ $this->convenios->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="whitespace-normal py-3">
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $convenioProceso->convenio?->nombre_con }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $convenioProceso->convenio?->codigo_con }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-3">
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $convenioProceso->proceso?->nombre_pro }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $convenioProceso->proceso?->semestre?->nombre_sem }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-3">
                            <div class="space-y-2">
                                <flux:badge color="{{ $convenioProceso->convenio?->tipo_beneficio_con?->getColor() ?? 'zinc' }}" size="lg">
                                    {{ $convenioProceso->convenio?->tipo_beneficio_con?->getNombre() ?? 'SIN BENEFICIO' }}
                                </flux:badge>
                                @if ($convenioProceso->convenio?->monto_descuento_con)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Monto: S/ {{ number_format((float) $convenioProceso->convenio->monto_descuento_con, 2) }}</div>
                                @elseif ($convenioProceso->convenio?->porcentaje_descuento_con)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Porcentaje: {{ number_format((float) $convenioProceso->convenio->porcentaje_descuento_con, 2) }} %</div>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-3">
                            <div class="space-y-1">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $convenioProceso->cupo_maximo_cpr }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Usado: {{ $convenioProceso->cupo_usado_cpr }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-3">
                            <div class="space-y-1">
                                <div>{{ $convenioProceso->fecha_inicio_cpr?->format('d/m/Y') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $convenioProceso->fecha_fin_cpr?->format('d/m/Y') }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>{{ $convenioProceso->inscripciones_count + $convenioProceso->reglas_pago_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $convenioProceso->estado_cpr->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$convenioProceso->estado_cpr->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $convenioProceso->estado_cpr->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $convenioProceso->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('convenio_editar')
                                            <flux:menu.item wire:click="cargarConvenio({{ $convenioProceso->id_cpr }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('convenio_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $convenioProceso->id_cpr }}, '{{ $convenioProceso->convenio?->nombre_con }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('convenio_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $convenioProceso->id_cpr }}, '{{ $convenioProceso->convenio?->nombre_con }}')"
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
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron convenios.</p>
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
            Mostrando {{ $this->convenios->firstItem() ?? 0 }} - {{ $this->convenios->lastItem() ?? 0 }} de {{ $this->convenios->total() }} registros
        </div>
        {{ $this->convenios->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('convenio_crear') || auth()->user()?->hasPermisoAccion('convenio_editar'))
        <flux:modal flyout variant="floating" name="modal-convenio" class="px-0! py-5! md:w-lg!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.input
                                label="Codigo"
                                type="text"
                                wire:model="convenioForm.codigo"
                                :invalid="$errors->has('convenioForm.codigo')"
                                :error="$errors->first('convenioForm.codigo')"
                                required
                                floating
                            />

                            <x-ui.input
                                label="Nombre"
                                type="text"
                                wire:model="convenioForm.nombre"
                                :invalid="$errors->has('convenioForm.nombre')"
                                :error="$errors->first('convenioForm.nombre')"
                                required
                                floating
                            />
                        </div>

                        <x-ui.textarea
                            label="Descripcion"
                            wire:model="convenioForm.descripcion"
                            :invalid="$errors->has('convenioForm.descripcion')"
                            :error="$errors->first('convenioForm.descripcion')"
                            autoResize
                            floating
                        />

                        <div class="grid gap-5 md:grid-cols-3">
                            <x-ui.select
                                label="Tipo de beneficio"
                                wire:model.live="convenioForm.tipo_beneficio"
                                :invalid="$errors->has('convenioForm.tipo_beneficio')"
                                :error="$errors->first('convenioForm.tipo_beneficio')"
                                required
                                floating
                            >
                                @foreach ($this->tiposBeneficio() as $tipoBeneficio)
                                    <option value="{{ $tipoBeneficio->value }}">{{ $tipoBeneficio->getNombre() }}</option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                label="Monto descuento"
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="convenioForm.monto_descuento"
                                :invalid="$errors->has('convenioForm.monto_descuento')"
                                :error="$errors->first('convenioForm.monto_descuento')"
                                :disabled="$convenioForm['tipo_beneficio'] === \App\Enums\TipoBeneficioConvenio::ExoneracionTotal->value"
                                floating
                            />

                            <x-ui.input
                                label="Porcentaje descuento"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                wire:model="convenioForm.porcentaje_descuento"
                                :invalid="$errors->has('convenioForm.porcentaje_descuento')"
                                :error="$errors->first('convenioForm.porcentaje_descuento')"
                                :disabled="$convenioForm['tipo_beneficio'] === \App\Enums\TipoBeneficioConvenio::ExoneracionTotal->value"
                                floating
                            />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.select
                                label="Proceso"
                                wire:model="convenioForm.id_proceso"
                                :invalid="$errors->has('convenioForm.id_proceso')"
                                :error="$errors->first('convenioForm.id_proceso')"
                                required
                                floating
                            >
                                <option value="">Seleccionar proceso</option>
                                @foreach ($this->procesosDisponibles as $proceso)
                                    <option wire:key="convenio-proceso-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                label="Cupo maximo"
                                type="number"
                                min="1"
                                step="1"
                                wire:model="convenioForm.cupo_maximo"
                                :invalid="$errors->has('convenioForm.cupo_maximo')"
                                :error="$errors->first('convenioForm.cupo_maximo')"
                                required
                                floating
                            />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.input
                                label="Fecha inicio"
                                type="date"
                                wire:model="convenioForm.fecha_inicio"
                                :invalid="$errors->has('convenioForm.fecha_inicio')"
                                :error="$errors->first('convenioForm.fecha_inicio')"
                                required
                                floating
                            />

                            <x-ui.input
                                label="Fecha fin"
                                type="date"
                                wire:model="convenioForm.fecha_fin"
                                :invalid="$errors->has('convenioForm.fecha_fin')"
                                :error="$errors->first('convenioForm.fecha_fin')"
                                required
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
        this.$js.cargarAlertaEstado = (id_convenio_proceso, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Â¿Esta seguro de que desea cambiar el estado del convenio "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'convenio-cambiar-estado',
                confirmParams: { id_convenio_proceso: id_convenio_proceso },
            });
        };

        this.$js.cargarAlertaEliminar = (id_convenio_proceso, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Â¿Esta seguro de que desea eliminar el convenio "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'convenio-eliminar',
                confirmParams: { id_convenio_proceso: id_convenio_proceso },
            });
        };
    </script>
@endscript
