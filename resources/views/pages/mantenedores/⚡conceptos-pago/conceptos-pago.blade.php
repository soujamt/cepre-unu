@section ('icon')
    <x-header.icon icon="receipt" />
@endsection

@section ('header')
    <x-header.title>Conceptos de pago</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('conceptos-pago.index') }}" wire:navigate>Conceptos de pago</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('concepto_pago_editar')
            || auth()->user()?->hasPermisoAccion('concepto_pago_estado')
            || auth()->user()?->hasPermisoAccion('concepto_pago_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar por código o nombre" clearable>
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

        @permiso ('concepto_pago_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarConcepto">
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
                    <x-table.heading class="w-28">CÓDIGO</x-table.heading>
                    <x-table.heading>NOMBRE</x-table.heading>
                    <x-table.heading class="w-28">CUOTAS</x-table.heading>
                    <x-table.heading class="w-24">REGLAS</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>REGISTRO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->conceptos as $concepto)
                    <x-table.row wire:key="concepto-{{ $concepto->id_cpg }}">
                        <x-table.cell>{{ $this->conceptos->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <span class="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $concepto->codigo_cpg }}</span>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2 whitespace-normal">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100">{{ $concepto->nombre_cpg }}</span>
                            @if ($concepto->descripcion_cpg)
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $concepto->descripcion_cpg }}</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            @if ($concepto->permite_cuotas_cpg)
                                <flux:badge size="sm" color="blue">Permite</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Pago único</flux:badge>
                            @endif
                        </x-table.cell>
                        <x-table.cell>{{ $concepto->reglas_pago_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $concepto->estado_cpg->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$concepto->estado_cpg->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $concepto->estado_cpg->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2">
                            @if ($concepto->creado_en)
                                <span class="block tabular-nums">{{ $concepto->creado_en->format('d/m/Y') }}</span>
                                <span class="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{{ $concepto->creado_en->format('h:i a') }}</span>
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
                                        @permiso ('concepto_pago_editar')
                                            <flux:menu.item wire:click="cargarConcepto({{ $concepto->id_cpg }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('concepto_pago_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $concepto->id_cpg }}, {{ \Illuminate\Support\Js::from($concepto->nombre_cpg) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('concepto_pago_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $concepto->id_cpg }}, {{ \Illuminate\Support\Js::from($concepto->nombre_cpg) }})"
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
                        <x-table.cell colspan="7" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron conceptos de pago registrados.</p>
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
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->conceptos->firstItem() ?? 0 }} - {{ $this->conceptos->lastItem() ?? 0 }} de {{ $this->conceptos->total() }} registros
        </div>
        {{ $this->conceptos->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('concepto_pago_crear') || auth()->user()?->hasPermisoAccion('concepto_pago_editar'))
        <flux:modal flyout variant="floating" name="modal-concepto" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <x-ui.input
                            label="Código"
                            type="text"
                            wire:model="conceptoForm.codigo"
                            :invalid="$errors->has('conceptoForm.codigo')"
                            :error="$errors->first('conceptoForm.codigo')"
                            maxlength="20"
                            required
                            floating
                        />

                        <x-ui.input
                            label="Nombre"
                            type="text"
                            wire:model="conceptoForm.nombre"
                            :invalid="$errors->has('conceptoForm.nombre')"
                            :error="$errors->first('conceptoForm.nombre')"
                            required
                            floating
                        />

                        <x-ui.textarea
                            label="Descripción"
                            wire:model="conceptoForm.descripcion"
                            :invalid="$errors->has('conceptoForm.descripcion')"
                            :error="$errors->first('conceptoForm.descripcion')"
                            autoResize
                            floating
                        />

                        <div class="space-y-1.5">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-3 transition-colors hover:bg-zinc-100/80 dark:border-white/[0.06] dark:bg-white/4 dark:hover:bg-white/7">
                                <input
                                    type="checkbox"
                                    wire:model="conceptoForm.permite_cuotas"
                                    class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-index-500 dark:border-zinc-600 dark:bg-zinc-900"
                                />
                                <span class="space-y-0.5">
                                    <span class="block text-sm font-medium text-zinc-800 dark:text-zinc-100">Permite cuotas</span>
                                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">Las reglas de pago con este concepto podrán dividirse en cuotas.</span>
                                </span>
                            </label>
                            @error ('conceptoForm.permite_cuotas')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
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
        this.$js.cargarAlertaEstado = (id_concepto, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado de el concepto de pago "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'concepto-cambiar-estado',
                confirmParams: { id_concepto: id_concepto },
            });
        };

        this.$js.cargarAlertaEliminar = (id_concepto, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar el concepto de pago "' + nombre + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'concepto-eliminar',
                confirmParams: { id_concepto: id_concepto },
            });
        };
    </script>
@endscript
