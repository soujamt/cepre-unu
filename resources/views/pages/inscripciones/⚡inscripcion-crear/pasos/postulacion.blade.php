@php
    $contextoProceso = $this->contextoProcesoPostulacion;
    $procesoActivo = $this->procesoPostulacionActivo;
@endphp
<x-inscripcion.encabezado-paso
    :paso="4"
    :total="count($this->pasos)"
    :icono="$this->pasos[4]['icono']"
    titulo="Postulación"
    descripcion="Elija sede, carrera y turno del proceso vigente; la modalidad sale del año de egreso."
/>
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
            <p class="text-sm">No podremos continuar con la postulación hasta que exista un proceso habilitado con carreras y modalidades activas.</p>
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

        @php
            $modalidadPostulacion = $this->modalidadPostulacion;
        @endphp

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Modalidad</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Se asigna sola según el año de egreso ingresado en el paso anterior.</p>
            </div>

            @if ($modalidadPostulacion)
                <flux:callout variant="success" icon="check-circle" heading="{{ $modalidadPostulacion->nombre_mad }}">
                    <p class="text-sm">
                        Año de egreso {{ $generalForm['anio_graduacion'] }}: {{ mb_strtolower($modalidadPostulacion->condicion_egreso_mad?->getNombre() ?? '', 'UTF-8') }}.
                    </p>
                </flux:callout>
            @else
                <flux:callout variant="warning" icon="exclamation-triangle" heading="Sin modalidad para el año de egreso {{ $generalForm['anio_graduacion'] }}">
                    <p class="text-sm">El proceso activo no tiene habilitada la modalidad que corresponde. Revise Configuración académica › Oferta del proceso.</p>
                </flux:callout>
            @endif
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Sede, carrera y turno</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Solo se muestran las carreras habilitadas en la sede para este proceso.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <x-ui.select
                        label="Sede"
                        name="id_sede"
                        wire:model.live="postulacionForm.id_sede"
                        :invalid="$errors->has('postulacionForm.id_sede')"
                        :error="$errors->first('postulacionForm.id_sede')"
                        required
                        floating
                    >
                        <option value="">Seleccionar sede</option>
                        @foreach ($this->sedesPostulacionDisponibles as $sede)
                            <option value="{{ $sede['id'] }}">{{ $sede['nombre'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="lg:col-span-7" wire:key="carrera-sede-{{ $postulacionForm['id_sede'] }}">
                    <x-ui.select-filter
                        label="Carrera profesional"
                        name="id_carrera"
                        wire:model.live="postulacionForm.id_carrera"
                        :invalid="$errors->has('postulacionForm.id_carrera')"
                        :error="$errors->first('postulacionForm.id_carrera')"
                        :disabled="$postulacionForm['id_sede'] === ''"
                        required
                        floating
                    >
                        <option value="">{{ $postulacionForm['id_sede'] === '' ? 'Primero seleccione la sede' : 'Seleccionar carrera' }}</option>
                        @foreach ($this->carrerasPostulacionDisponibles as $carrera)
                            <option value="{{ $carrera['id'] }}">{{ $carrera['nombre'] }}</option>
                        @endforeach
                    </x-ui.select-filter>
                </div>

                <fieldset class="lg:col-span-12">
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Turno <span aria-hidden="true">*</span></legend>
                    <div class="flex flex-wrap gap-3">
                        @forelse ($this->turnosPostulacionDisponibles as $turno)
                            <label
                                wire:key="turno-{{ $turno['id'] }}"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200/80 bg-white px-3 py-2 text-sm text-zinc-700 has-[:checked]:border-zinc-900 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-200 dark:has-[:checked]:border-white"
                            >
                                <input
                                    type="radio"
                                    name="id_turno"
                                    value="{{ $turno['id'] }}"
                                    wire:model.live="postulacionForm.id_turno"
                                    required
                                    @if ($errors->has('postulacionForm.id_turno')) aria-invalid="true" aria-describedby="error-turno" @endif
                                    class="size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                                />
                                {{ $turno['nombre'] }}
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">El proceso no tiene aulas asignadas en ningún turno.</p>
                        @endforelse
                    </div>
                    @error('postulacionForm.id_turno')
                        <p id="error-turno" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </fieldset>
            </div>
        </section>

        @php
            $sedeElegida = $this->sedesPostulacionDisponibles->firstWhere('id', $postulacionForm['id_sede']);
            $carreraElegida = $this->carrerasPostulacionDisponibles->firstWhere('id', $postulacionForm['id_carrera']);
            $turnoElegido = $this->turnosPostulacionDisponibles->firstWhere('id', $postulacionForm['id_turno']);
            $reglaElegida = $this->reglaPagoSeleccionada;
            $resumen = [
                'Postulante' => trim(($personaForm['apellido_paterno'] ?? '').' '.($personaForm['apellido_materno'] ?? '').', '.($personaForm['nombres'] ?? ''), ' ,') ?: '—',
                'Documento' => $personaForm['numero_documento'] ?: '—',
                'Apoderado' => trim(($personaForm['nombre_apoderado'] ?? '').' '.($personaForm['celular_apoderado'] ?? '')) ?: '—',
                'Modalidad de pago' => $reglaElegida?->conceptoPago?->nombre_cpg ?? '—',
                'Pagos marcados' => 'S/ '.number_format($this->montoPagosSeleccionados, 2),
                'Modalidad' => $modalidadPostulacion?->nombre_mad ?? '—',
                'Sede' => $sedeElegida['nombre'] ?? '—',
                'Carrera' => $carreraElegida['nombre'] ?? '—',
                'Turno' => $turnoElegido['nombre'] ?? '—',
            ];
        @endphp

        <section class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40" aria-labelledby="titulo-resumen-inscripcion">
            <div class="space-y-1">
                <p id="titulo-resumen-inscripcion" class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Revisión</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Revise los datos con el postulante antes de registrar. Para corregir algo, vuelva al paso correspondiente.</p>
            </div>

            <div class="flex flex-col gap-5 sm:flex-row">
                <div class="flex shrink-0 flex-col items-center gap-2">
                    @if ($fotoActual = $this->fotoPostulanteActual)
                        <img
                            src="{{ $this->fuenteFotoPostulante($fotoActual) }}"
                            alt="Foto del postulante"
                            width="96"
                            height="128"
                            class="h-32 w-24 rounded-md object-cover ring-1 ring-black/10 dark:ring-white/10"
                        />
                    @else
                        <div class="flex h-32 w-24 flex-col items-center justify-center gap-1 rounded-md border border-dashed border-zinc-300 text-center text-xs text-zinc-500 dark:border-white/15 dark:text-zinc-400">
                            <x-ui.icon name="user" size="6" class="text-zinc-400" />
                            Sin foto
                        </div>
                    @endif

                    @if ($this->puedeCambiarFoto())
                    <label class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-100 focus-within:ring-2 focus-within:ring-zinc-400 dark:text-zinc-200 dark:hover:bg-white/5">
                        <x-ui.icon name="upload" size="4" />
                        <span wire:loading.remove wire:target="fotoPostulante">{{ $fotoActual ? 'Cambiar foto' : 'Subir foto' }}</span>
                        <span wire:loading wire:target="fotoPostulante">Subiendo…</span>
                        <input type="file" accept="image/jpeg,image/png" wire:model="fotoPostulante" class="sr-only" />
                    </label>
                    @endif
                    @error ('fotoPostulante')
                        <p class="max-w-36 text-center text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <dl class="grid flex-1 content-start gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($resumen as $etiqueta => $valor)
                        <div class="min-w-0">
                            <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $etiqueta }}</dt>
                            <dd class="text-sm font-medium break-words text-zinc-800 dark:text-zinc-100">{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div>
                <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <input
                        type="checkbox"
                        name="confirmacion_datos"
                        wire:model="confirmacionDatos"
                        @if ($errors->has('confirmacionDatos')) aria-invalid="true" aria-describedby="error-confirmacion" @endif
                        class="mt-0.5 size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800"
                    />
                    <span>Confirmo que los datos son correctos y fueron revisados con el postulante.</span>
                </label>
                @error ('confirmacionDatos')
                    <p id="error-confirmacion" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar se registra la inscripción, se asigna un aula y los pagos validados quedan REGISTRADOS.</p>

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
                        >Registrar inscripción</span
                    >
                    <span wire:loading wire:target="guardarPasoPostulacion">Guardando...</span>
                </flux:button>
            </div>
        </div>
    </form>
@endif
</div>
