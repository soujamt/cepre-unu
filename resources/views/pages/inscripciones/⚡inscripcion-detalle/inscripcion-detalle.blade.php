@section ('icon')
    <x-header.icon icon="clipboard-detail" />
@endsection

@section ('header')
    <x-header.title>Detalle de inscripción</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.index') }}" wire:navigate>Inscripciones</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item>{{ $inscripcionActual->codigo_ins }}</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $resumenPagoGeneral = $this->resumenPagoGeneral();
        $resumenExpedientesGeneral = $this->resumenExpedientesGeneral();
        $tonoPorColor = fn (string $color): string => match ($color) {
            'green', 'emerald', 'lime' => 'success',
            'amber', 'yellow', 'orange' => 'warning',
            'red', 'rose' => 'danger',
            'sky', 'blue', 'violet', 'purple', 'indigo' => 'info',
            default => 'neutral',
        };
    @endphp

    <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card sm:p-6 dark:border-white/[0.06] dark:bg-zinc-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:badge color="{{ $inscripcionActual->estado_ins->getColor() }}">
                        <x-slot name="icon">
                            <x-ui.icon :name="$inscripcionActual->estado_ins->getIcon()" size="4" class="me-1" />
                        </x-slot>
                        {{ $inscripcionActual->estado_ins->getNombre() }}
                    </flux:badge>
                    <span
                        class="rounded-md border border-zinc-200/80 bg-zinc-50 px-2 py-0.5 text-xs font-medium text-zinc-600 tabular-nums dark:border-white/10 dark:bg-white/5 dark:text-zinc-300"
                    >
                        {{ $inscripcionActual->codigo_ins }}
                    </span>
                </div>

                <div class="space-y-1">
                    <h2 class="text-xl font-semibold tracking-tight text-zinc-900 sm:text-2xl dark:text-white">
                        {{ $this->nombrePostulante() }}
                    </h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->descripcionPostulacion() }}</p>
                </div>

                <dl class="flex flex-wrap gap-x-6 gap-y-2 pt-1 text-sm">
                    @foreach ([
                        ['icono' => 'discount', 'label' => 'Convenio', 'valor' => $this->convenioResumen()],
                        ['icono' => 'clock', 'label' => 'Turno', 'valor' => $this->turnoResumen()],
                        ['icono' => 'building', 'label' => 'Aula', 'valor' => $this->aulaResumen()],
                    ] as $dato)
                        <div class="flex items-center gap-2">
                            <x-ui.icon :name="$dato['icono']" size="4" class="text-zinc-400 dark:text-zinc-500" />
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $dato['label'] }}:</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dato['valor'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <flux:button wire:click="volverListado" class="shrink-0">
                <x-slot name="icon">
                    <x-ui.icon name="arrow-left" size="5" />
                </x-slot>
                Volver al listado
            </flux:button>
        </div>
    </div>

    <x-resumen>
        <x-resumen.item
            label="Proceso"
            :value="$inscripcionActual->procesoOferta?->proceso?->nombre_pro ?? 'SIN PROCESO'"
            icon="layers"
            tono="info"
            detalle-icon="calendar"
            :detalle="($inscripcionActual->procesoOferta?->proceso?->fecha_inicio_pro?->format('d/m/Y') ?? 'Sin fechas').' al '.($inscripcionActual->procesoOferta?->proceso?->fecha_fin_pro?->format('d/m/Y') ?? '—')"
            destacado
        />

        <x-resumen.item
            label="Pago"
            :value="'S/ '.number_format((float) $inscripcionActual->obligacionesPago->sum('monto_pagado_iop'), 2)"
            icon="wallet"
            :tono="$tonoPorColor($resumenPagoGeneral['color'])"
            detalle-icon="dollar-circle"
            :detalle="$resumenPagoGeneral['label']"
            :title="$resumenPagoGeneral['detalle']"
        />

        <x-resumen.item
            label="Expedientes"
            :value="$inscripcionActual->expedientes->count()"
            icon="folder-open"
            :tono="$tonoPorColor($resumenExpedientesGeneral['color'])"
            detalle-icon="file-detail"
            :detalle="$resumenExpedientesGeneral['label']"
            :title="$resumenExpedientesGeneral['detalle']"
        />

        <x-resumen.item
            label="Registro"
            :value="$inscripcionActual->fecha_inscripcion_ins?->format('d/m/Y') ?? 'N/A'"
            icon="calendar-check"
            detalle-icon="clock"
            :detalle="$inscripcionActual->fecha_inscripcion_ins?->format('h:i a') ?? 'Sin hora'"
        />
    </x-resumen>

    <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Información personal</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Datos del postulante</h3>
                    </div>
                    <flux:badge color="zinc" size="lg">
                        {{ $inscripcionActual->persona?->tipoDocumento?->nombre_tdo ?? 'DOC' }}
                    </flux:badge>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Número de documento</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->numero_documento_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Correo electrónico</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->correo_electronico_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Celular</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->celular_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Teléfono</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->telefono_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha de nacimiento</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->fecha_nacimiento_per?->format('d/m/Y') ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Sexo</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->sexo_per?->value ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Dirección</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->direccion_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Ubigeo de nacimiento</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $this->ubigeoCompleto($inscripcionActual->persona?->ubigeoNacimiento) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Ubigeo de domicilio</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $this->ubigeoCompleto($inscripcionActual->persona?->ubigeoDomicilio) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Postulación</p>
                <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Configuración académica</h3>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Carrera</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Sede</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->ofertaBase?->sedeCarrera?->sede?->nombre_sed ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Modalidad</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Lugar de inscripción</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Turno</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->turno?->nombre_tur ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Aula asignada</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $this->aulaResumen() }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Convenio</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->convenioProceso?->convenio?->nombre_con ?? 'Sin convenio' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Año de graduación</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->anio_graduacion_ins ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Colegio</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $inscripcionActual->colegio?->nombre_col ?? ($inscripcionActual->nombre_colegio_extranjero_ins ?: 'No registrado') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pagos</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Historial validado</h3>
                    </div>
                    <flux:badge color="zinc" size="lg">{{ $inscripcionActual->obligacionesPago->count() }}</flux:badge>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($inscripcionActual->obligacionesPago as $obligacion)
                        <div
                            class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 p-4 dark:border-white/[0.06] dark:bg-zinc-950/40"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $obligacion->reglaPago?->conceptoPago?->nombre_cpg ?? 'SIN CONCEPTO' }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Estado: {{ $obligacion->estado_iop->value }} | Total: S/ {{ number_format((float) $obligacion->monto_total_iop, 2) }}
                                    </p>
                                </div>
                                <flux:badge color="{{ $obligacion->estado_iop->getColor() }}" size="lg">
                                    {{ $obligacion->estado_iop->getNombre() }}
                                </flux:badge>
                            </div>

                            @if ($obligacion->pagos->isNotEmpty())
                                <div class="mt-3 space-y-2">
                                    @foreach ($obligacion->pagos as $pago)
                                        <div
                                            class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-zinc-900"
                                        >
                                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->resumenPago($pago) }}</p>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                Validacion: {{ $pago->estado_validacion_ipa->value }}
                                                @if ($pago->usuarioValidador)
                                                    | Valido: {{ $pago->usuarioValidador->nombres_usu ?? $pago->usuarioValidador->correo_usu }}
                                                @endif
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No hay pagos asociados a esta obligación.</p>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/[0.06] dark:bg-zinc-950/40 dark:text-zinc-400"
                        >
                            Esta inscripción no registra obligaciones de pago.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Expedientes</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Revisión documental</h3>
                    </div>
                    <flux:badge color="zinc" size="lg">{{ $inscripcionActual->expedientes->count() }}</flux:badge>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($inscripcionActual->expedientes as $expediente)
                        <div
                            class="rounded-xl border border-zinc-200/80 bg-zinc-50/80 p-4 dark:border-white/[0.06] dark:bg-zinc-950/40"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $expediente->tipoExpediente?->nombre_tex ?? 'SIN TIPO' }}
                                </p>
                                <flux:badge color="{{ $expediente->estado_revision_iex->getColor() }}" size="lg">
                                    {{ $expediente->estado_revision_iex->getNombre() }}
                                </flux:badge>
                            </div>

                            <div class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                                @php ($urlArchivo = $this->urlArchivoExpediente($expediente))
                                <div class="flex items-center justify-between gap-2">
                                    <p>Archivo: {{ $expediente->nombre_archivo_iex ?: 'No registrado' }}</p>
                                    @if ($urlArchivo)
                                        <a
                                            href="{{ $urlArchivo }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            <x-ui.icon name="arrow-out-up-right-square" size="3.5" />
                                            Abrir
                                        </a>
                                    @endif
                                </div>
                                <p>Observación: {{ $expediente->observacion_iex ?: 'Sin observación' }}</p>
                                <p>
                                    Revision: {{ $expediente->revisado_en?->format('d/m/Y h:i a') ?? 'No registrada' }}
                                    @if ($expediente->usuarioRevisor)
                                        | {{ $expediente->usuarioRevisor->nombres_usu ?? $expediente->usuarioRevisor->correo_usu }}
                                    @endif
                                </p>
                            </div>

                            @permiso ('inscripcion_editar')
                                <div
                                    class="mt-3 flex items-center gap-2 border-t border-zinc-200 pt-3 dark:border-white/10"
                                >
                                    <flux:button
                                        size="xs"
                                        variant="filled"
                                        wire:click="aprobarExpediente({{ $expediente->id_iex }})"
                                        :disabled="$expediente->estado_revision_iex === \App\Enums\EstadoRevisionExpediente::Aprobado"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="check-circle" size="3.5" />
                                        </x-slot>
                                        Aprobar
                                    </flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="filled"
                                        wire:click="abrirModalRevision({{ $expediente->id_iex }}, 'OBSERVADO')"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="info-circle" size="3.5" />
                                        </x-slot>
                                        Observar
                                    </flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="danger"
                                        wire:click="abrirModalRevision({{ $expediente->id_iex }}, 'RECHAZADO')"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="x-circle" size="3.5" />
                                        </x-slot>
                                        Rechazar
                                    </flux:button>
                                </div>
                            @endpermiso
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/[0.06] dark:bg-zinc-950/40 dark:text-zinc-400"
                        >
                            Esta inscripción no tiene expedientes registrados.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <flux:modal name="modal-revisar-expediente" class="md:w-md!">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $estadoRevision === 'OBSERVADO' ? 'Observar expediente' : 'Rechazar expediente' }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $estadoRevision === 'OBSERVADO'
                        ? 'Indique la observacion para que el postulante corrija el documento.'
                        : 'Indique el motivo del rechazo del documento.' }}
                </flux:text>
            </div>

            <flux:field>
                <flux:label>{{ $estadoRevision === 'OBSERVADO' ? 'Observacion' : 'Motivo de rechazo' }}</flux:label>
                <flux:textarea
                    wire:model="observacionRevision"
                    placeholder="Describa el motivo (mínimo 5 caracteres)"
                    rows="3"
                />
                <flux:error name="observacionRevision" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('modal-revisar-expediente').close()">
                    Cancelar
                </flux:button>
                <flux:button
                    variant="{{ $estadoRevision === 'RECHAZADO' ? 'danger' : 'primary' }}"
                    wire:click="confirmarRevision"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="checkbox-checked" size="4" />
                    </x-slot>
                    Confirmar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
