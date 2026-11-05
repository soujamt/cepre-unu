@php ($contextoProceso = $this->contextoProcesoPostulacion)
@php ($procesoActivo = $this->procesoPostulacionActivo)
<div
class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div class="space-y-1">
    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 3</p>
    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Postulación</h3>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la oferta activa del proceso vigente y deje listo el borrador de inscripción.</p>
</div>
</div>
<div
class="relative rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div
    wire:loading.flex
    wire:target="guardarPasoPostulacion"
    class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
>
    <div class="flex flex-col items-center gap-3 text-center">
        <x-ui.icon name="loader-lines" size="10" class="text-accent animate-spin" />
        <div>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando postulación</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
        </div>
    </div>
</div>

@if (! $contextoProceso['disponible'])
    <div class="space-y-5">
        <flux:callout
            variant="warning"
            icon="exclamation-circle"
            heading="{{ $contextoProceso['mensaje'] }}"
        >
            <p class="text-sm">No podremos continuar con la postulación hasta que exista un proceso habilitado y con ofertas activas.</p>
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
    <form
        wire:submit="guardarPasoPostulacion"
        class="space-y-5"
        x-on:duplicado-confirmado.window="$wire.confirmarGuardadoDuplicado()"
        x-on:duplicado-cancelado.window="$wire.cancelarDuplicadoPostulacion()"
    >
        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso vigente</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Este paso usará el proceso disponible según la fecha y la configuración académica activa.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <x-ui.input
                        label="Proceso"
                        :value="$procesoActivo?->nombre_pro"
                        disabled
                        floating
                    />
                </div>

                <div class="lg:col-span-6">
                    <x-ui.input
                        label="Vigencia"
                        :value="$procesoActivo ? $procesoActivo->fecha_inicio_pro?->format('d/m/Y h:i a').' al '.$procesoActivo->fecha_fin_pro?->format('d/m/Y h:i a') : null"
                        disabled
                        floating
                    />
                </div>
            </div>

            <flux:callout
                variant="success"
                icon="check-circle"
                heading="{{ $contextoProceso['mensaje'] }}"
            >
                <p class="text-sm">
                    Ventana activa: {{ $procesoActivo?->fecha_inicio_pro?->format('d/m/Y h:i a') }} - {{ $procesoActivo?->fecha_fin_pro?->format('d/m/Y h:i a') }}
                </p>
            </flux:callout>
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Selección de oferta</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">La selección se construye en cascada para mostrar solo combinaciones válidas del proceso actual.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                {{-- Modalidad: siempre visible --}}
                <div class="lg:col-span-6">
                    <x-ui.select
                        label="Modalidad"
                        wire:model.live="postulacionForm.id_modalidad_admision"
                        :invalid="$errors->has('postulacionForm.id_modalidad_admision')"
                        :error="$errors->first('postulacionForm.id_modalidad_admision')"
                        required
                        floating
                    >
                        <option value="">Seleccionar modalidad</option>
                        @foreach ($this->modalidadesPostulacionDisponibles as $modalidad)
                            <option value="{{ $modalidad['id'] }}">
                                {{ $modalidad['nombre'] }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                {{-- Lugar: aparece tras seleccionar modalidad --}}
                @if ($postulacionForm['id_modalidad_admision'] !== '')
                    <div
                        class="lg:col-span-6"
                        wire:key="lugar-{{ $postulacionForm['id_modalidad_admision'] }}"
                    >
                        <x-ui.select
                            label="Lugar de inscripción"
                            wire:model.live="postulacionForm.id_lugar_inscripcion"
                            :invalid="$errors->has('postulacionForm.id_lugar_inscripcion')"
                            :error="$errors->first('postulacionForm.id_lugar_inscripcion')"
                            required
                            floating
                        >
                            <option value="">Seleccionar lugar</option>
                            @foreach ($this->lugaresPostulacionDisponibles as $lugar)
                                <option value="{{ $lugar['id'] }}">{{ $lugar['nombre'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                @endif

                {{-- Turno: aparece tras seleccionar lugar --}}
                @if ($postulacionForm['id_lugar_inscripcion'] !== '')
                    <div
                        class="lg:col-span-4"
                        wire:key="turno-{{ $postulacionForm['id_modalidad_admision'] }}-{{ $postulacionForm['id_lugar_inscripcion'] }}"
                    >
                        <x-ui.select
                            label="Turno"
                            wire:model.live="postulacionForm.id_turno"
                            :invalid="$errors->has('postulacionForm.id_turno')"
                            :error="$errors->first('postulacionForm.id_turno')"
                            required
                            floating
                        >
                            <option value="">Seleccionar turno</option>
                            @foreach ($this->turnosPostulacionDisponibles as $turno)
                                <option value="{{ $turno['id'] }}">{{ $turno['nombre'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                @endif

                {{-- Oferta: aparece tras seleccionar turno --}}
                @if ($postulacionForm['id_turno'] !== '')
                    <div
                        class="lg:col-span-8"
                        wire:key="oferta-{{ $postulacionForm['id_modalidad_admision'] }}-{{ $postulacionForm['id_lugar_inscripcion'] }}-{{ $postulacionForm['id_turno'] }}"
                    >
                        <x-ui.select-filter
                            label="Oferta"
                            wire:model.live="postulacionForm.id_proceso_oferta"
                            :invalid="$errors->has('postulacionForm.id_proceso_oferta')"
                            :error="$errors->first('postulacionForm.id_proceso_oferta')"
                            required
                            floating
                        >
                            <option value="">Seleccionar oferta</option>
                            @foreach ($this->procesoOfertasPostulacionDisponibles as $procesoOferta)
                                <option value="{{ $procesoOferta->id_pof }}">
                                    {{ $this->descripcionProcesoOferta($procesoOferta) }}
                                </option>
                            @endforeach
                        </x-ui.select-filter>
                    </div>
                @endif
            </div>
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Condición especial</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Si corresponde, seleccione un convenio vigente para este mismo proceso.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-12">
                    <x-ui.select
                        label="Convenio"
                        wire:model.live="postulacionForm.id_convenio_proceso"
                        :invalid="$errors->has('postulacionForm.id_convenio_proceso')"
                        :error="$errors->first('postulacionForm.id_convenio_proceso')"
                        floating
                    >
                        <option value="">Sin convenio</option>
                        @foreach ($this->conveniosPostulacionDisponibles as $convenioProceso)
                            <option value="{{ $convenioProceso->id_cpr }}">
                                {{ $this->descripcionConvenioProceso($convenioProceso) }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen de postulación</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Antes de continuar, revise que la modalidad, oferta y condición especial sean las correctas.</p>
            </div>

            @php ($ofertaSeleccionada = $this->procesoOfertasPostulacionDisponibles->firstWhere('id_pof', (int) $postulacionForm['id_proceso_oferta']))
            @php ($convenioSeleccionado = $this->conveniosPostulacionDisponibles->firstWhere('id_cpr', (int) $postulacionForm['id_convenio_proceso']))

            <flux:callout
                variant="{{ $ofertaSeleccionada ? 'success' : 'secondary' }}"
                icon="{{ $ofertaSeleccionada ? 'check-circle' : 'information-circle' }}"
                heading="{{ $ofertaSeleccionada ? 'La postulacion ya tiene una oferta seleccionada.' : 'Complete modalidad, lugar, turno y oferta para continuar.' }}"
            >
                @if ($ofertaSeleccionada)
                    <div class="space-y-1 text-sm">
                        <p><span class="font-medium">Oferta:</span> {{ $this->descripcionProcesoOferta($ofertaSeleccionada) }}</p>
                        <p><span class="font-medium">Convenio:</span> {{ $this->descripcionConvenioProceso($convenioSeleccionado) }}</p>
                    </div>
                @endif
            </flux:callout>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso se creará o actualizará el borrador de inscripción con la oferta seleccionada.</p>

            <div class="flex gap-3">
                <flux:button
                    variant="filled"
                    type="button"
                    wire:click="irPasoAnterior"
                    class="font-normal"
                >
                    Volver
                </flux:button>

                <flux:button variant="primary" type="submit" class="font-normal">
                    <x-slot name="icon">
                        <x-ui.icon name="chevron-right-circle" size="5" class="text-white" />
                    </x-slot>
                    <span wire:loading.remove wire:target="guardarPasoPostulacion"
                        >Guardar y continuar</span
                    >
                    <span wire:loading wire:target="guardarPasoPostulacion">Guardando...</span>
                </flux:button>
            </div>
        </div>
    </form>
@endif
</div>
