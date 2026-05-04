@section ('icon')
    <x-header.icon icon="shield-check" />
@endsection

@section ('header')
    <x-header.title>VALIDAR PAGOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('pagos.index') }}" wire:navigate>PAGOS</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('pagos.validar') }}" wire:navigate>VALIDAR PAGOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div class="grid gap-5 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold tracking-wide text-zinc-800 dark:text-zinc-100">BUSCAR OBLIGACION</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Busque por codigo de inscripcion, DNI o nombre del postulante.
                        </p>
                    </div>

                    <x-ui.input
                        label="Inscripcion o postulante"
                        type="text"
                        wire:model.live.debounce.400ms="buscarObligacion"
                        iconLeading="magnifying-glass"
                        floating
                    />

                    @if ($buscarObligacion !== '')
                        <flux:button variant="ghost" size="sm" wire:click="limpiarBusquedaObligacion">
                            Limpiar busqueda
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold tracking-wide text-zinc-800 dark:text-zinc-100">OBLIGACIONES PENDIENTES</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Seleccione la obligacion sobre la que se validara el pago.
                        </p>
                    </div>

                    <flux:badge color="zinc" size="lg">{{ $this->obligacionesDisponibles->count() }}</flux:badge>
                </div>

                <div class="space-y-3">
                    @forelse ($this->obligacionesDisponibles as $obligacion)
                        <button
                            type="button"
                            wire:key="obligacion-disponible-{{ $obligacion->id_iop }}"
                            wire:click="seleccionarObligacion({{ $obligacion->id_iop }})"
                            @class([
                                'w-full rounded-xl border p-4 text-left transition',
                                'border-zinc-900 bg-zinc-50 shadow-xs dark:border-white dark:bg-zinc-950/70' => $idObligacionSeleccionada === $obligacion->id_iop,
                                'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20' => $idObligacionSeleccionada !== $obligacion->id_iop,
                            ])
                        >
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $this->resumenObligacion($obligacion) }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $obligacion->reglaPago?->conceptoPago?->nombre_cpg ?? 'SIN CONCEPTO' }}
                                        </p>
                                    </div>

                                    <flux:badge color="{{ $obligacion->estado_iop->getColor() }}" size="lg">
                                        {{ $obligacion->estado_iop->getNombre() }}
                                    </flux:badge>
                                </div>

                                <div class="grid gap-2 text-xs text-zinc-500 dark:text-zinc-400 sm:grid-cols-3">
                                    <p>Total: <span class="font-medium text-zinc-700 dark:text-zinc-200">S/ {{ number_format((float) $obligacion->monto_total_iop, 2) }}</span></p>
                                    <p>Pagado: <span class="font-medium text-zinc-700 dark:text-zinc-200">S/ {{ number_format((float) $obligacion->monto_pagado_iop, 2) }}</span></p>
                                    <p>Saldo: <span class="font-medium text-zinc-700 dark:text-zinc-200">S/ {{ number_format((float) $obligacion->saldo_iop, 2) }}</span></p>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-white/10">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="wallet" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">No se encontraron obligaciones pendientes con ese criterio.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @if ($this->obligacionSeleccionada)
                @php($obligacionSeleccionada = $this->obligacionSeleccionada)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Obligacion seleccionada</p>
                                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">{{ $this->resumenObligacion($obligacionSeleccionada) }}</h3>
                            </div>

                            <div class="grid gap-2 text-sm text-zinc-500 dark:text-zinc-400 sm:grid-cols-2">
                                <p>Concepto: <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $obligacionSeleccionada->reglaPago?->conceptoPago?->nombre_cpg ?? 'SIN CONCEPTO' }}</span></p>
                                <p>Cuotas: <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $obligacionSeleccionada->numero_cuotas_iop }}</span></p>
                                <p>Total: <span class="font-medium text-zinc-700 dark:text-zinc-200">S/ {{ number_format((float) $obligacionSeleccionada->monto_total_iop, 2) }}</span></p>
                                <p>Saldo: <span class="font-medium text-zinc-700 dark:text-zinc-200">S/ {{ number_format((float) $obligacionSeleccionada->saldo_iop, 2) }}</span></p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                            <flux:badge color="{{ $obligacionSeleccionada->estado_iop->getColor() }}" size="lg">
                                {{ $obligacionSeleccionada->estado_iop->getNombre() }}
                            </flux:badge>

                            <flux:button variant="ghost" size="sm" wire:click="limpiarSeleccionObligacion">
                                Cambiar obligacion
                            </flux:button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h3 class="text-sm font-semibold tracking-wide text-zinc-800 dark:text-zinc-100">BUSCAR PAGO IMPORTADO</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                Valide por DNI, operacion visible y fecha del pago registrado por el banco.
                            </p>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <x-ui.input
                                label="DNI"
                                type="text"
                                wire:model.live="dniBusqueda"
                                :invalid="$errors->has('dniBusqueda')"
                                :error="$errors->first('dniBusqueda')"
                                floating
                                required
                            />

                            <x-ui.input
                                label="Operacion"
                                type="text"
                                wire:model.live="numeroOperacionBusqueda"
                                :invalid="$errors->has('numeroOperacionBusqueda')"
                                :error="$errors->first('numeroOperacionBusqueda')"
                                floating
                                required
                            />

                            <x-ui.input
                                label="Fecha de pago"
                                type="date"
                                wire:model.live="fechaPagoBusqueda"
                                :invalid="$errors->has('fechaPagoBusqueda')"
                                :error="$errors->first('fechaPagoBusqueda')"
                                floating
                                required
                            />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                La busqueda utiliza pagos importados que aun no hayan sido asociados.
                            </p>

                            <flux:button variant="primary" wire:click="buscarPagos" class="font-normal">
                                <x-slot name="icon">
                                    <x-ui.icon name="magnifying-glass" size="5" class="text-white" />
                                </x-slot>
                                Buscar pago
                            </flux:button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold tracking-wide text-zinc-800 dark:text-zinc-100">RESULTADOS DE LA BUSQUEDA</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Seleccione el pago correcto y confirme la validacion.
                                </p>
                            </div>

                            <flux:badge color="zinc" size="lg">{{ $this->detallesEncontrados->count() }}</flux:badge>
                        </div>

                        @if ($this->detallesEncontrados->isNotEmpty())
                            <div class="grid gap-3 xl:grid-cols-2">
                                @foreach ($this->detallesEncontrados as $detalle)
                                    <button
                                        type="button"
                                        wire:key="detalle-busqueda-{{ $detalle->id_ibd }}"
                                        wire:click="seleccionarDetalle({{ $detalle->id_ibd }})"
                                        @class([
                                            'rounded-xl border p-4 text-left transition',
                                            'border-zinc-900 bg-zinc-50 shadow-xs dark:border-white dark:bg-zinc-950/70' => $idDetalleSeleccionado === $detalle->id_ibd,
                                            'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20' => $idDetalleSeleccionado !== $detalle->id_ibd,
                                        ])
                                    >
                                        <div class="space-y-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="space-y-1">
                                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                                        {{ $detalle->nombre_depositante_ibd ?: 'SIN NOMBRE REGISTRADO' }}
                                                    </p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->resumenDetalle($detalle) }}</p>
                                                    @if ($detalle->numero_operacion_raw_ibd && $detalle->numero_operacion_visible_ibd && $detalle->numero_operacion_raw_ibd !== $detalle->numero_operacion_visible_ibd)
                                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            Operacion banco: {{ $detalle->numero_operacion_raw_ibd }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <flux:badge color="{{ $detalle->estado_ibd->getColor() }}" size="lg">
                                                    {{ $detalle->estado_ibd->getNombre() }}
                                                </flux:badge>
                                            </div>

                                            <div class="flex items-center justify-between gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                                                <p>{{ $detalle->codigo_oficina_ibd }} {{ $detalle->codigo_canal_ibd }}</p>
                                                <p class="font-medium">S/ {{ number_format((float) $detalle->monto_ibd, 2) }}</p>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-zinc-950/40">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $this->detalleSeleccionado ? $this->resumenDetalle($this->detalleSeleccionado) : 'Seleccione un pago para continuar' }}
                                    </p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $this->detalleSeleccionado ? 'Monto seleccionado: S/ '.number_format((float) $this->detalleSeleccionado->monto_ibd, 2) : 'El sistema asociara el pago a la obligacion seleccionada.' }}
                                    </p>
                                </div>

                                <flux:button
                                    variant="primary"
                                    wire:click="confirmarPago"
                                    class="font-normal"
                                    :disabled="$idDetalleSeleccionado === null"
                                >
                                    <x-slot name="icon">
                                        <x-ui.icon name="check-circle" size="5" class="text-white" />
                                    </x-slot>
                                    Confirmar pago
                                </flux:button>
                            </div>
                        @elseif ($busquedaRealizada)
                            <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-white/10">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No se encontraron pagos importados con ese DNI, operacion y fecha.</p>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-white/10">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <x-ui.icon name="magnifying-glass" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Complete la busqueda para ver pagos disponibles.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-xs dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="shield-check" size="14" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Seleccione una obligacion de pago para iniciar la validacion.</p>
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                            Una vez seleccionada, podra buscar el pago importado por DNI, operacion visible y fecha del deposito.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
