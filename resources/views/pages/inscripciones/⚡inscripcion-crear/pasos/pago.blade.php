@php ($contextoProceso = $this->contextoProcesoPostulacion)
@php ($reglaPagoSeleccionada = $this->reglaPagoSeleccionada)
@php ($montoMinimo = $this->montoMinimoRegla($reglaPagoSeleccionada))
@php ($montoMarcado = $this->montoPagosSeleccionados)
@php ($modoPublico = $this->esModoPublico())
<x-inscripcion.encabezado-paso
    :paso="1"
    :total="count($this->pasos)"
    :icono="$this->pasos[1]['icono']"
    titulo="Validación de pago"
    descripcion="Ingrese el DNI y el número de operación del pago. Solo se puede inscribir con un pago LIBRE del postulante."
/>
<div class="relative rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
    <div
        wire:loading.flex
        wire:target="buscarPagosPasoPago,confirmarPasoPago"
        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
    >
        <div class="flex flex-col items-center gap-3 text-center">
            <x-ui.icon name="loader-lines" size="10" class="text-accent animate-spin" />
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Validando el pago</p>
        </div>
    </div>

    @if (! $contextoProceso['disponible'])
        <div class="space-y-5">
            <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $contextoProceso['mensaje'] }}">
                <p class="text-sm">No se puede inscribir hasta que exista un proceso habilitado con carreras y modalidades activas.</p>
            </flux:callout>
            <flux:button variant="filled" wire:click="volverListado" class="font-normal">{{ $modoPublico ? 'Salir' : 'Volver al listado' }}</flux:button>
        </div>
    @elseif ($this->reglasPagoDisponibles->isEmpty())
        <div class="space-y-5">
            <flux:callout variant="warning" icon="exclamation-circle" heading="No hay modalidades de pago vigentes en el proceso activo.">
                <p class="text-sm">{{ $modoPublico ? 'La inscripción en línea aún no está disponible. Consulte en el CEPRE-UNU.' : 'Registre las reglas de pago del proceso en Configuración académica › Reglas de pago.' }}</p>
            </flux:callout>
            <flux:button variant="filled" wire:click="volverListado" class="font-normal">{{ $modoPublico ? 'Salir' : 'Volver al listado' }}</flux:button>
        </div>
    @elseif ($inscripcionId !== null && $this->tienePagoValidadoWizard)
        <div class="space-y-5">
            <flux:callout variant="success" icon="check-circle" heading="Esta inscripción ya tiene su pago registrado.">
                <p class="text-sm">Las cuotas siguientes se registran desde Inscripciones › Validar pagos.</p>
            </flux:callout>
            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="irPaso(2)" class="font-normal">Continuar con los datos</flux:button>
            </div>
        </div>
    @else
        <div class="space-y-5">
            <form wire:submit="buscarPagosPasoPago" class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40">
                <div class="space-y-1">
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Buscar pago</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Proceso {{ $contextoProceso['proceso']?->nombre_pro }}. {{ $modoPublico ? 'Use el DNI del postulante y el número de operación del voucher. Si pagó hoy, el pago puede tardar en aparecer.' : 'El pago debe estar cargado desde el TXT del banco o registrado en Pagos.' }}</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-5">
                        <x-ui.input
                            label="DNI / CE del postulante"
                            type="text"
                            name="numero_documento"
                            inputmode="numeric"
                            maxlength="12"
                            autocomplete="off"
                            wire:model.blur="pagoForm.numero_documento"
                            :invalid="$errors->has('pagoForm.numero_documento')"
                            :error="$errors->first('pagoForm.numero_documento')"
                            :disabled="$personaId !== null"
                            required
                            floating
                        />
                    </div>
                    <div class="lg:col-span-4">
                        <x-ui.input
                            label="N° de operación"
                            type="text"
                            name="numero_operacion"
                            inputmode="numeric"
                            autocomplete="off"
                            wire:model="pagoForm.numero_operacion"
                            :invalid="$errors->has('pagoForm.numero_operacion')"
                            :error="$errors->first('pagoForm.numero_operacion')"
                            required
                            floating
                        />
                    </div>
                    <div class="flex items-start lg:col-span-3">
                        <flux:button type="submit" variant="primary" class="h-12 w-full font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="search" size="5" class="text-white" />
                            </x-slot>
                            Verificar pago
                        </flux:button>
                    </div>
                </div>
            </form>

            @if ($busquedaPagoRealizada)
                <fieldset class="space-y-3 rounded-lg border border-zinc-200/80 p-4 dark:border-white/10">
                    <legend class="px-1 text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pagos libres del postulante</legend>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Marque los pagos que se usan para esta inscripción; se suman para alcanzar el mínimo.</p>

                    <div class="space-y-2">
                        @foreach ($this->detallesPagoEncontrados as $detalle)
                            <label
                                wire:key="pago-libre-{{ $detalle->id_ibd }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200/80 px-3 py-2 text-sm has-[:checked]:border-zinc-400 dark:border-white/10 dark:has-[:checked]:border-white/30"
                            >
                                <input
                                    type="checkbox"
                                    wire:model.live="pagosSeleccionados.{{ $detalle->id_ibd }}"
                                    class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                                />
                                <span class="flex flex-1 flex-wrap items-center gap-x-4 gap-y-1">
                                    <span class="font-medium text-zinc-800 tabular-nums dark:text-zinc-100">S/ {{ number_format((float) $detalle->monto_ibd, 2) }}</span>
                                    <span class="text-zinc-500 tabular-nums dark:text-zinc-400">Op. {{ $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd }}</span>
                                    <span class="text-zinc-500 tabular-nums dark:text-zinc-400">{{ $detalle->fecha_pago_ibd?->format('d/m/Y') }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ $detalle->ubicacion_ibd }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40">
                    <div class="grid gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-7">
                            <x-ui.select
                                label="Modalidad de pago"
                                name="id_regla_pago"
                                wire:model.live="pagoForm.id_regla_pago"
                                :invalid="$errors->has('pagoForm.id_regla_pago')"
                                :error="$errors->first('pagoForm.id_regla_pago')"
                                required
                                floating
                            >
                                <option value="">Seleccionar modalidad de pago</option>
                                @foreach ($this->reglasPagoDisponibles as $reglaPago)
                                    <option wire:key="regla-{{ $reglaPago->id_rpa }}" value="{{ $reglaPago->id_rpa }}">{{ $this->descripcionReglaPago($reglaPago) }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        @if ($reglaPagoSeleccionada?->documento_requerido_rpa)
                            <div class="lg:col-span-5">
                                <x-ui.input
                                    :label="$reglaPagoSeleccionada->documento_requerido_rpa"
                                    type="text"
                                    name="numero_documento_beneficio"
                                    autocomplete="off"
                                    wire:model="pagoForm.numero_documento_beneficio"
                                    :invalid="$errors->has('pagoForm.numero_documento_beneficio')"
                                    :error="$errors->first('pagoForm.numero_documento_beneficio')"
                                    required
                                    floating
                                />
                            </div>
                        @endif
                    </div>

                    @if ($reglaPagoSeleccionada)
                        @php ($alcanza = $montoMarcado >= $montoMinimo)
                        <flux:callout
                            variant="{{ $alcanza ? 'success' : 'warning' }}"
                            icon="{{ $alcanza ? 'check-circle' : 'exclamation-triangle' }}"
                            heading="Marcado S/ {{ number_format($montoMarcado, 2) }} de un mínimo de S/ {{ number_format($montoMinimo, 2) }}"
                        >
                            <p class="text-sm">
                                {{ $alcanza ? 'El pago alcanza para inscribir.' : 'Falta S/ '.number_format($montoMinimo - $montoMarcado, 2).($modoPublico ? '. Marque otro pago suyo o acérquese al CEPRE-UNU.' : '. Marque otro pago del postulante o registre el pago en Pagos.') }}
                            </p>
                        </flux:callout>
                    @endif

                    @error ('pagosSeleccionados')
                        <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Los pagos quedan REGISTRADOS recién al terminar la inscripción.</p>

                <div class="flex gap-3">
                    <flux:button variant="filled" type="button" wire:click="volverListado" class="font-normal">Cancelar</flux:button>
                    <flux:button variant="primary" type="button" wire:click="confirmarPasoPago" class="font-normal">
                        <x-slot name="icon">
                            <x-ui.icon name="chevron-right-circle" size="5" class="text-white" />
                        </x-slot>
                        Validar y continuar
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
