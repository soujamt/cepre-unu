@php ($inscripcionBorrador = $this->inscripcionBorrador)
@php ($requisitosExpediente = $this->requisitosExpedienteWizard)
<x-inscripcion.encabezado-paso
    :paso="5"
    :total="count($this->pasos)"
    :icono="$this->pasos[5]['icono']"
    titulo="Expedientes"
    descripcion="Revise los requisitos del proceso y registre el estado de cada expediente presentado por el postulante."
/>
<div
class="relative rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div
    wire:loading.flex
    wire:target="guardarPasoExpedientes"
    class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
>
    <div class="flex flex-col items-center gap-3 text-center">
        <x-ui.icon name="loader-lines" size="10" class="text-accent animate-spin" />
        <div>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando expedientes</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
        </div>
    </div>
</div>

@if ($inscripcionBorrador === null)
    <div class="space-y-6">
        <flux:callout
            variant="warning"
            icon="exclamation-circle"
            heading="No existe una inscripción borrador para registrar expedientes."
        >
            <p class="text-sm">Primero debemos completar los pasos previos del wizard antes de cerrar la revisión documental.</p>
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
@elseif ($requisitosExpediente->isEmpty())
    <div class="space-y-6">
        <flux:callout
            variant="warning"
            icon="exclamation-circle"
            heading="No hay requisitos de expediente configurados para este proceso."
        >
            <p class="text-sm">Revise el mantenedor de requisitos antes de intentar finalizar la inscripción.</p>
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
    <form wire:submit="guardarPasoExpedientes" class="space-y-6">
        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen de cierre</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">La inscripción se activará solo si todos los expedientes obligatorios quedan aprobados.</p>
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
                        label="Estado actual"
                        :value="$inscripcionBorrador->estado_ins->value"
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
            </div>
        </section>

        <section
            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Revisión documental</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre el estado de revisión, observaciones y una referencia simple del documento recibido.</p>
            </div>

            <div class="space-y-4">
                @foreach ($expedientesForm as $indice => $expediente)
                    @php ($requisito = $requisitosExpediente->firstWhere('id_tipo_expediente', (int) $expediente['id_tipo_expediente']))
                    @php ($esObligatorio = $requisito?->obligatorio_rex ?? false)
                    <div
                        wire:key="expediente-wizard-{{ $expediente['id_tipo_expediente'] }}"
                        class="rounded-xl border border-zinc-200/80 bg-white dark:border-white/[0.06] dark:bg-zinc-900 shadow-card"
                    >
                        {{-- Cabecera del expediente --}}
                        <div
                            class="flex items-start justify-between gap-3 rounded-t-xl border-b border-zinc-100 bg-zinc-50/60 px-4 py-3 dark:border-white/10 dark:bg-zinc-950/30"
                        >
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                    {{ $requisito?->tipoExpediente?->nombre_tex ?? 'EXPEDIENTE' }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $requisito?->tipoExpediente?->descripcion_tex ?? 'Sin descripcion registrada.' }}
                                </p>
                            </div>
                            <flux:badge
                                :color="$esObligatorio ? 'red' : 'zinc'"
                                class="shrink-0"
                            >
                                {{ $esObligatorio ? 'Obligatorio' : 'Opcional' }}
                            </flux:badge>
                        </div>

                        {{-- Cuerpo del expediente --}}
                        <div class="space-y-4 p-4">
                            {{-- Zona de carga de archivo --}}
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                    Archivo PDF
                                    @if ($esObligatorio && ! $expediente['archivo_url'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>

                                <div
                                    @class ([
                                        'rounded-xl border border-dashed p-3',
                                        'border-red-300 bg-red-50/40 dark:border-red-500/40 dark:bg-red-500/5' => $errors->has('archivosExpediente.'.$indice),
                                        'border-zinc-300 bg-zinc-50/80 dark:border-white/10 dark:bg-zinc-950/40' => ! $errors->has('archivosExpediente.'.$indice),
                                    ])
                                >
                                    <input
                                        type="file"
                                        wire:model="archivosExpediente.{{ $indice }}"
                                        accept="application/pdf,.pdf"
                                        class="block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-white dark:file:text-zinc-900 dark:hover:file:bg-zinc-100"
                                    />
                                </div>

                                @if ($expediente['archivo_url'])
                                    <div
                                        class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                                    >
                                        <x-ui.icon name="file" size="4" class="text-red-500" />
                                        <span>Archivo actual:</span>
                                        <a
                                            href="{{ asset('storage/'.$expediente['archivo_url']) }}"
                                            target="_blank"
                                            class="text-accent font-medium underline"
                                            >{{ $expediente['nombre_archivo'] ?: 'ver archivo' }}</a
                                        >
                                        <span class="text-zinc-400 dark:text-zinc-600"
                                            >— suba uno nuevo para reemplazarlo</span
                                        >
                                    </div>
                                @endif

                                @error ('archivosExpediente.'.$indice)
                                    <p class="flex items-center gap-1 ps-1 text-xs font-medium text-red-500 dark:text-red-400">
                                        <x-ui.icon name="alert-circle" size="4" />
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Estado y observacion --}}
                            <div class="grid gap-4 lg:grid-cols-2">
                                <x-ui.select
                                    label="Estado de revisión"
                                    wire:model="expedientesForm.{{ $indice }}.estado_revision"
                                    :invalid="$errors->has('expedientesForm.'.$indice.'.estado_revision')"
                                    :error="$errors->first('expedientesForm.'.$indice.'.estado_revision')"
                                    required
                                    floating
                                >
                                    @foreach ($this->estadosRevisionExpediente() as $estadoRevision)
                                        <option value="{{ $estadoRevision->value }}">
                                            {{ $estadoRevision->value }}
                                        </option>
                                    @endforeach
                                </x-ui.select>

                                <x-ui.textarea
                                    label="Observación"
                                    wire:model="expedientesForm.{{ $indice }}.observacion"
                                    :invalid="$errors->has('expedientesForm.'.$indice.'.observacion')"
                                    :error="$errors->first('expedientesForm.'.$indice.'.observacion')"
                                    rows="2"
                                    floating
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso se registrará la revisión documental y se cerrará el wizard de inscripción.</p>

            <div class="flex gap-3">
                <flux:button
                    variant="filled"
                    type="button"
                    wire:click="irPasoAnterior"
                    wire:loading.attr="disabled"
                    wire:target="guardarPasoExpedientes"
                    class="font-normal"
                >
                    Volver
                </flux:button>

                <flux:button variant="primary" type="submit" class="font-normal">
                    <x-slot name="icon">
                        <x-ui.icon name="check" size="5" class="text-white" />
                    </x-slot>
                    <span wire:loading.remove wire:target="guardarPasoExpedientes"
                        >Guardar y finalizar</span
                    >
                    <span wire:loading wire:target="guardarPasoExpedientes">Guardando...</span>
                </flux:button>
            </div>
        </div>
    </form>
@endif
</div>
