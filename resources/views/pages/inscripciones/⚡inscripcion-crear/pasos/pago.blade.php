@php ($inscripcionBorrador = $this->inscripcionBorrador)
@php ($reglaPagoSeleccionada = $this->reglaPagoSeleccionada)
@php ($obligacionPagoSeleccionada = $this->obligacionPagoSeleccionada)
<div
class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div class="space-y-1">
    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 4</p>
    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Pago</h3>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la regla de pago, busque la operación bancaria y valide el abono del postulante.</p>
</div>
</div>
<div
class="relative rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div
    wire:loading.flex
    wire:target="buscarPagosPasoPago,confirmarPasoPago"
    class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
>
    <div class="flex flex-col items-center gap-3 text-center">
        <x-ui.icon name="loader-lines" size="10" class="text-accent animate-spin" />
        <div>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Procesando validación de pago</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
        </div>
    </div>
</div>

@if ($inscripcionBorrador === null)
    <div class="space-y-5">
        <flux:callout
            variant="warning"
            icon="exclamation-circle"
            heading="No existe una inscripción borrador para validar pagos."
        >
            <p class="text-sm">Primero debemos guardar la postulación para poder asociar una regla de pago y validar la operación bancaria.</p>
        </flux:callout>

        <div class="flex gap-3">
            <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                Volver al paso anterior
            </flux:button>

            <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                Volver al listado
            </flux:button>
        </div>
    </div>
@elseif ($this->reglasPagoDisponibles->isEmpty())
    <div class="space-y-5">
        <flux:callout
            variant="warning"
            icon="exclamation-circle"
            heading="No hay reglas de pago vigentes para esta postulación."
        >
            <p class="text-sm">Revise la configuración de reglas de pago del proceso actual antes de continuar con la validación bancaria.</p>
        </flux:callout>

        <div class="flex gap-3">
            <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                Volver al paso anterior
            </flux:button>

            <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                Volver al listado
            </flux:button>
        </div>
    </div>
@else
    <div class="space-y-5">
        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Contexto de inscripción</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Validaremos el pago sobre la inscripción borrador ya creada en el paso anterior.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Código inscripción"
                        :value="$inscripcionBorrador->codigo_ins"
                        disabled
                        floating
                    />
                </div>
                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Postulante"
                        :value="trim(implode(' ', array_filter([
                            $inscripcionBorrador->persona?->apellido_paterno_per,
                            $inscripcionBorrador->persona?->apellido_materno_per,
                            $inscripcionBorrador->persona?->nombres_per,
                        ])))"
                        disabled
                        floating
                    />
                </div>
                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Oferta seleccionada"
                        :value="$this->descripcionProcesoOferta($inscripcionBorrador->procesoOferta)"
                        disabled
                        floating
                    />
                </div>
            </div>
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Regla de pago</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la tarifa que corresponde a esta inscripción antes de buscar el pago del banco.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-12">
                    <x-ui.select
                        label="Regla de pago"
                        wire:model.live="pagoForm.id_regla_pago"
                        :invalid="$errors->has('pagoForm.id_regla_pago')"
                        :error="$errors->first('pagoForm.id_regla_pago')"
                        required
                        floating
                    >
                        <option value="">Seleccionar regla</option>
                        @foreach ($this->reglasPagoDisponibles as $reglaPago)
                            <option value="{{ $reglaPago->id_rpa }}">
                                {{ $this->descripcionReglaPago($reglaPago) }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            @if ($reglaPagoSeleccionada)
                <flux:callout
                    variant="secondary"
                    icon="information-circle"
                    heading="Resumen de la regla seleccionada"
                >
                    <div class="grid gap-3 text-sm lg:grid-cols-2">
                        <p><span class="font-medium">Concepto:</span> {{ $reglaPagoSeleccionada->conceptoPago?->nombre_cpg }}</p>
                        <p><span class="font-medium">Monto total:</span> S/ {{ number_format((float) $reglaPagoSeleccionada->monto_total_rpa, 2) }}</p>
                        <p><span class="font-medium">Cuotas:</span> {{ $reglaPagoSeleccionada->permite_cuotas_rpa ? 'Hasta '.$reglaPagoSeleccionada->numero_maximo_cuotas_rpa : 'Pago unico' }}</p>
                        <p><span class="font-medium">Primera cuota mínima:</span> S/ {{ number_format((float) $reglaPagoSeleccionada->monto_minimo_primera_cuota_rpa, 2) }}</p>
                    </div>
                </flux:callout>
            @endif

            @if ($obligacionPagoSeleccionada)
                <flux:callout
                    variant="{{ $obligacionPagoSeleccionada->estado_iop->value === 'PAGADO' ? 'success' : 'warning' }}"
                    icon="{{ $obligacionPagoSeleccionada->estado_iop->value === 'PAGADO' ? 'check-circle' : 'clock' }}"
                    heading="Estado actual de la obligación"
                >
                    <div class="grid gap-3 text-sm lg:grid-cols-3">
                        <p><span class="font-medium">Pagado:</span> S/ {{ number_format((float) $obligacionPagoSeleccionada->monto_pagado_iop, 2) }}</p>
                        <p><span class="font-medium">Saldo:</span> S/ {{ number_format((float) $obligacionPagoSeleccionada->saldo_iop, 2) }}</p>
                        <p><span class="font-medium">Estado:</span> {{ $obligacionPagoSeleccionada->estado_iop->value }}</p>
                    </div>
                </flux:callout>
            @endif
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Busqueda de pago bancario</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Busque la operación usando DNI, operación visible y fecha del abono importado del banco.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Número documento"
                        wire:model="pagoForm.numero_documento"
                        disabled
                        :invalid="$errors->has('pagoForm.numero_documento')"
                        :error="$errors->first('pagoForm.numero_documento')"
                        floating
                    />
                </div>

                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Operación visible"
                        type="text"
                        wire:model="pagoForm.numero_operacion"
                        :invalid="$errors->has('pagoForm.numero_operacion')"
                        :error="$errors->first('pagoForm.numero_operacion')"
                        required
                        floating
                    />
                </div>

                <div class="lg:col-span-4">
                    <x-ui.input
                        label="Fecha de pago"
                        type="date"
                        wire:model="pagoForm.fecha_pago"
                        :invalid="$errors->has('pagoForm.fecha_pago')"
                        :error="$errors->first('pagoForm.fecha_pago')"
                        required
                        floating
                    />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button
                    type="button"
                    variant="primary"
                    color="purple"
                    wire:click="buscarPagosPasoPago"
                    wire:loading.attr="disabled"
                    wire:target="buscarPagosPasoPago"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="search" size="5" class="text-white" />
                    </x-slot>
                    <span wire:loading.remove wire:target="buscarPagosPasoPago">Buscar pago</span>
                    <span wire:loading wire:target="buscarPagosPasoPago">Buscando...</span>
                </flux:button>
            </div>
        </section>

        @if ($busquedaPagoRealizada)
            <section
                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
            >
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resultados importados</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione el pago correcto antes de confirmar la validación de la obligación.</p>
                </div>

                @if ($this->detallesPagoEncontrados->isEmpty())
                    <flux:callout
                        variant="warning"
                        icon="exclamation-circle"
                        heading="No se encontraron pagos pendientes con esos datos."
                    >
                        <p class="text-sm">Revise la operación visible, la fecha o confirme que el TXT del banco ya fue importado.</p>
                    </flux:callout>
                @else
                    <div class="grid gap-3">
                        @foreach ($this->detallesPagoEncontrados as $detallePago)
                            <button
                                type="button"
                                wire:key="detalle-pago-{{ $detallePago->id_ibd }}"
                                wire:click="seleccionarDetallePago({{ $detallePago->id_ibd }})"
                                wire:loading.attr="disabled"
                                wire:target="buscarPagosPasoPago,confirmarPasoPago,seleccionarDetallePago"
                                @class ([
                                    'w-full rounded-xl border px-4 py-3 text-left transition',
                                    'border-green-500 bg-green-50/70 dark:border-green-400 dark:bg-green-500/10' => (string) $pagoForm['id_detalle_banco'] === (string) $detallePago->id_ibd,
                                    'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20' => (string) $pagoForm['id_detalle_banco'] !== (string) $detallePago->id_ibd,
                                ])
                            >
                                <div
                                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                            {{ $this->resumenDetallePago($detallePago) }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Depositante: {{ $detallePago->nombre_depositante_ibd ?: 'SIN NOMBRE' }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Operacion banco: {{ $detallePago->numero_operacion_raw_ibd ?: $detallePago->numero_operacion_ibd ?: 'SIN OPERACION' }}
                                        </p>
                                    </div>

                                    <span
                                        class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        {{ $detallePago->estado_ibd->value }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if ($obligacionPagoSeleccionada && $obligacionPagoSeleccionada->pagos->isNotEmpty())
            <section
                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
            >
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pagos ya validados</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Esta obligación ya tiene cuotas registradas. Aquí puede revisar el historial acumulado.</p>
                </div>

                <div class="space-y-3">
                    @foreach ($obligacionPagoSeleccionada->pagos as $pagoRegistrado)
                        <div
                            class="rounded-xl border border-zinc-200/80 bg-white px-4 py-3 text-sm dark:border-white/[0.06] dark:bg-zinc-900 shadow-card"
                        >
                            <div
                                class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="space-y-1">
                                    <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                        Cuota {{ $pagoRegistrado->numero_cuota_ipa }} | S/ {{ number_format((float) $pagoRegistrado->monto_ipa, 2) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $pagoRegistrado->numero_operacion_ipa ?: 'SIN OPERACION' }} | {{ $pagoRegistrado->fecha_pago_ipa?->format('d/m/Y') }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-300"
                                >
                                    {{ $pagoRegistrado->estado_validacion_ipa->value }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($this->tienePagoValidadoWizard)
            <flux:callout
                variant="success"
                icon="check-circle"
                heading="Pago validado correctamente"
            >
                <p class="text-sm">Esta inscripción ya tiene un pago verificado. Puede continuar directamente al paso de expedientes.</p>
            </flux:callout>
            <div class="flex justify-end">
                <flux:button
                    variant="primary"
                    type="button"
                    wire:click="continuarPasoExpedientes"
                    class="font-normal"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="chevron-right-circle" size="5" class="text-white" />
                    </x-slot>
                    Continuar a expedientes
                </flux:button>
            </div>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cuando confirmemos el pago, quedará asociado a la inscripción y el wizard avanzará al paso de expedientes.</p>

                <div class="flex gap-3">
                    <flux:button
                        variant="filled"
                        type="button"
                        wire:click="irPasoAnterior"
                        wire:loading.attr="disabled"
                        wire:target="buscarPagosPasoPago,confirmarPasoPago"
                        class="font-normal"
                    >
                        Volver
                    </flux:button>

                    <flux:button
                        variant="primary"
                        type="button"
                        wire:click="confirmarPasoPago"
                        wire:loading.attr="disabled"
                        wire:target="confirmarPasoPago"
                        class="font-normal"
                    >
                        <x-slot name="icon">
                            <x-ui.icon name="check" size="5" class="text-white" />
                        </x-slot>
                        <span wire:loading.remove wire:target="confirmarPasoPago"
                            >Confirmar pago y continuar</span
                        >
                        <span wire:loading wire:target="confirmarPasoPago">Validando...</span>
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
@endif
</div>
