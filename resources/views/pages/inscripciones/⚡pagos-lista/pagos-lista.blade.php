@section ('icon')
    <x-header.icon icon="wallet" />
@endsection

@section ('header')
    <x-header.title>Pagos</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('pagos.index') }}" wire:navigate>Pagos</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeEditar = auth()->user()?->hasPermisoAccion('pago_editar') ?? false;
        $puedeEliminar = auth()->user()?->hasPermisoAccion('pago_eliminar') ?? false;
    @endphp

    <x-pagos.pestanas activa="pagos" />

    <x-resumen :columnas="3">
        <x-resumen.item label="Pagos" :value="$this->resumen['total']" icon="wallet" tono="info" detalle-icon="dollar-circle" :detalle="'S/ '.number_format($this->resumen['monto'], 2)" destacado />
        <x-resumen.item label="Libres" :value="$this->resumen['libres']" icon="clock" tono="warning" detalle-icon="info-circle" detalle="Aún sin inscripción" />
        <x-resumen.item label="Registrados" :value="$this->resumen['registrados']" icon="check-circle" tono="success" detalle-icon="link" detalle="Usados en una inscripción" />
    </x-resumen>

    <search class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar por DNI, operación o referencia" aria-label="Buscar pagos" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-48">
            <x-ui.select wire:model.live="filtroEstado" placeholder="Todos los estados" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="{{ App\Enums\EstadoImportacionBancoDetalle::Pendiente->value }}">Libres</option>
                <option value="{{ App\Enums\EstadoImportacionBancoDetalle::Asociado->value }}">Registrados</option>
            </x-ui.select>
        </div>

        <div class="flex w-full flex-wrap gap-2 sm:ms-auto sm:w-auto">
            @permiso ('pago_exportar_excel')
                <flux:button class="font-normal" wire:click="exportarExcel">
                    <x-slot name="icon">
                        <x-ui.icon name="arrow-in-down-square-half" size="5" />
                    </x-slot>
                    Exportar
                </flux:button>
            @endpermiso

            @permiso ('pago_crear')
                <flux:button class="font-normal" variant="primary" wire:click="cargarPago">
                    <x-slot name="icon">
                        <x-ui.icon name="plus" size="5" class="text-white" />
                    </x-slot>
                    Nuevo pago
                </flux:button>
            @endpermiso
        </div>
    </search>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading>FECHA</x-table.heading>
                    <x-table.heading>DNI</x-table.heading>
                    <x-table.heading>REFERENCIA</x-table.heading>
                    <x-table.heading class="text-end">IMPORTE</x-table.heading>
                    <x-table.heading>N° OPERACIÓN</x-table.heading>
                    <x-table.heading>UBICACIÓN</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading>N° REG.</x-table.heading>
                    <x-table.heading>OBSERVACIÓN</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->pagos as $pago)
                    @php
                        $inscripcion = $pago->inscripcionPago?->obligacionPago?->inscripcion;
                        $editable = $this->esEditable($pago);
                    @endphp
                    <x-table.row wire:key="pago-{{ $pago->id_ibd }}">
                        <x-table.cell class="tabular-nums">{{ $pago->fecha_pago_ibd?->format('d/m/Y') }}</x-table.cell>
                        <x-table.cell class="tabular-nums">{{ $pago->numero_documento_ibd }}</x-table.cell>
                        <x-table.cell class="max-w-56 truncate" title="{{ $pago->referencia_ibd ?? $pago->nombre_depositante_ibd }}">
                            {{ $pago->referencia_ibd ?? $pago->nombre_depositante_ibd ?? '—' }}
                        </x-table.cell>
                        <x-table.cell class="text-end tabular-nums">{{ number_format((float) $pago->monto_ibd, 2) }}</x-table.cell>
                        <x-table.cell class="tabular-nums">{{ $pago->numero_operacion_visible_ibd ?: $pago->numero_operacion_ibd }}</x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            <span class="block">{{ $pago->ubicacion_ibd ?? '—' }}</span>
                            @if ($pago->origen_ibd === 'MANUAL')
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">Registrado a mano</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $pago->estado_ibd->getColor() }}" size="sm">{{ $pago->estado_ibd->getNombre() }}</flux:badge>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">
                            @if ($inscripcion)
                                <a href="{{ route('inscripciones.detalle', encode($inscripcion->id_ins)) }}" wire:navigate class="font-medium text-index-600 tabular-nums hover:underline dark:text-index-300">{{ $inscripcion->codigo_ins }}</a>
                                <span class="block text-xs text-zinc-400 dark:text-zinc-500">{{ $inscripcion->sede?->nombre_sed }}</span>
                            @else
                                —
                            @endif
                        </x-table.cell>
                        <x-table.cell class="max-w-48 truncate" title="{{ $pago->observacion_ibd }}">{{ $pago->observacion_ibd ?? '—' }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeEditar || ($puedeEliminar && $editable))
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones del pago {{ $pago->numero_operacion_visible_ibd }}">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @if ($puedeEditar)
                                            <flux:menu.item wire:click="cargarPago({{ $pago->id_ibd }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                {{ $editable ? 'Editar' : 'Editar observación' }}
                                            </flux:menu.item>
                                        @endif
                                        @if ($puedeEliminar && $editable)
                                            <flux:menu.item variant="danger" wire:click="$js.cargarAlertaEliminar({{ $pago->id_ibd }}, {{ \Illuminate\Support\Js::from($pago->numero_operacion_visible_ibd) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="trash" size="4" class="me-2" />
                                                </x-slot>
                                                Eliminar
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                        <x-table.cell colspan="10" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="wallet" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No hay pagos con los filtros actuales.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50" wire:loading wire:target="buscar, filtroEstado, nextPage, previousPage, gotoPage, guardar, eliminar"></div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->pagos->firstItem() ?? 0 }} - {{ $this->pagos->lastItem() ?? 0 }} de {{ $this->pagos->total() }} pagos
        </div>
        {{ $this->pagos->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('pago_crear') || $puedeEditar)
        <flux:modal flyout variant="floating" name="modal-pago" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5" id="titulo-modal-pago">
                    {{ $editingId === null ? 'Registrar pago' : ($soloObservacion ? 'Observación del pago' : 'Editar pago') }}
                </flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5" aria-labelledby="titulo-modal-pago">
                    <div class="space-y-5 px-5">
                        @if (! $soloObservacion)
                            <x-ui.input label="Fecha" type="date" name="fecha" wire:model="pagoForm.fecha" :invalid="$errors->has('pagoForm.fecha')" :error="$errors->first('pagoForm.fecha')" required floating />
                            <x-ui.input label="DNI" type="text" name="dni" inputmode="numeric" maxlength="12" autocomplete="off" wire:model="pagoForm.dni" :invalid="$errors->has('pagoForm.dni')" :error="$errors->first('pagoForm.dni')" required floating />
                            <x-ui.input label="Referencia" type="text" name="referencia" wire:model="pagoForm.referencia" :invalid="$errors->has('pagoForm.referencia')" :error="$errors->first('pagoForm.referencia')" hint="Nombre o dato que figura en el voucher." floating />
                            <x-ui.input label="Importe (S/)" type="text" name="importe" inputmode="decimal" wire:model="pagoForm.importe" :invalid="$errors->has('pagoForm.importe')" :error="$errors->first('pagoForm.importe')" hint="Parentesco UNU se registra con 0." required floating />
                            <x-ui.input label="N° de operación" type="text" name="numero_operacion" inputmode="numeric" autocomplete="off" wire:model="pagoForm.numero_operacion" :invalid="$errors->has('pagoForm.numero_operacion')" :error="$errors->first('pagoForm.numero_operacion')" hint="Para parentesco, el N° de constancia de RRHH." required floating />
                            <x-ui.select label="Ubicación" name="ubicacion" wire:model="pagoForm.ubicacion" :invalid="$errors->has('pagoForm.ubicacion')" :error="$errors->first('pagoForm.ubicacion')" required floating>
                                @foreach ($this::UBICACIONES as $ubicacion)
                                    <option value="{{ $ubicacion }}">{{ $ubicacion }}</option>
                                @endforeach
                            </x-ui.select>
                        @endif
                        <x-ui.textarea label="Observación" name="observacion" wire:model="pagoForm.observacion" :invalid="$errors->has('pagoForm.observacion')" :error="$errors->first('pagoForm.observacion')" hint="Por ejemplo: SOLICITÓ DEVOLUCIÓN." autoResize floating />
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
                            </x-slot>
                            Guardar pago
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endif
</div>

@script
    <script>
        this.$js.cargarAlertaEliminar = (id_pago, operacion) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar el pago con operación "' + operacion + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'pago-eliminar',
                confirmParams: { id_pago: id_pago },
            });
        };
    </script>
@endscript
