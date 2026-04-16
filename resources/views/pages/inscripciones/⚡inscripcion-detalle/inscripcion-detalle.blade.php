@section ('icon')
    <x-header.icon icon="clipboard-text" />
@endsection

@section ('header')
    <x-header.title>DETALLE DE INSCRIPCION</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.index') }}" wire:navigate>INSCRIPCIONES</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item>{{ $inscripcionActual->codigo_ins }}</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php ($resumenPagoGeneral = $this->resumenPagoGeneral())
    @php ($resumenExpedientesGeneral = $this->resumenExpedientesGeneral())

    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div
            class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-linear-to-br from-white via-zinc-50 to-emerald-50 p-6 shadow-xs lg:flex-1 dark:border-white/10 dark:from-zinc-900 dark:via-zinc-900 dark:to-emerald-950/30"
        >
            <div
                class="pointer-events-none absolute -top-16 right-0 size-40 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-500/10"
            ></div>

            <div class="relative space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:badge color="{{ $inscripcionActual->estado_ins->getColor() }}" size="lg">
                        <x-slot name="icon">
                            <x-ui.icon :name="$inscripcionActual->estado_ins->getIcon()" size="4" class="me-1" />
                        </x-slot>
                        {{ $inscripcionActual->estado_ins->getNombre() }}
                    </flux:badge>
                    <flux:badge color="zinc" size="lg">{{ $inscripcionActual->codigo_ins }}</flux:badge>
                    <flux:badge
                        color="{{ $resumenPagoGeneral['color'] }}"
                        size="lg"
                        >{{ $resumenPagoGeneral['label'] }}</flux:badge
                    >
                    <flux:badge
                        color="{{ $resumenExpedientesGeneral['color'] }}"
                        size="lg"
                        >{{ $resumenExpedientesGeneral['label'] }}</flux:badge
                    >
                </div>

                <div class="space-y-2">
                    <h2 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">
                        {{ $this->nombrePostulante() }}
                    </h2>
                    <p class="max-w-4xl text-sm text-zinc-600 dark:text-zinc-300">{{ $this->descripcionPostulacion() }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="rounded-xl border border-white/60 bg-white/80 px-4 py-3 backdrop-blur-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Convenio</p>
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $this->convenioResumen() }}</p>
                    </div>

                    <div
                        class="rounded-xl border border-white/60 bg-white/80 px-4 py-3 backdrop-blur-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Turno</p>
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $this->turnoResumen() }}</p>
                    </div>

                    <div
                        class="rounded-xl border border-white/60 bg-white/80 px-4 py-3 backdrop-blur-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pago</p>
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $resumenPagoGeneral['detalle'] }}</p>
                    </div>

                    <div
                        class="rounded-xl border border-white/60 bg-white/80 px-4 py-3 backdrop-blur-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Expedientes</p>
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $resumenExpedientesGeneral['detalle'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <flux:button variant="filled" wire:click="volverListado" class="font-normal">
            <x-slot name="icon">
                <x-ui.icon name="arrow-left" size="5" />
            </x-slot>
            Volver al listado
        </flux:button>
    </div>

    <div class="grid gap-4 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso</p>
            <p class="mt-3 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $inscripcionActual->procesoOferta?->proceso?->nombre_pro ?? 'SIN PROCESO' }}
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $inscripcionActual->procesoOferta?->proceso?->semestre?->nombre_sem ?? 'SIN SEMESTRE' }}
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pago</p>
                    <p class="mt-3 text-lg font-semibold text-zinc-900 dark:text-white">
                        S/ {{ number_format((float) $inscripcionActual->obligacionesPago->sum('monto_pagado_iop'), 2) }}
                    </p>
                </div>
                <flux:badge
                    color="{{ $resumenPagoGeneral['color'] }}"
                    size="lg"
                    >{{ $resumenPagoGeneral['label'] }}</flux:badge
                >
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $inscripcionActual->obligacionesPago->count() }} obligacion(es)
            </p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Expedientes</p>
                    <p class="mt-3 text-lg font-semibold text-zinc-900 dark:text-white">{{ $inscripcionActual->expedientes->count() }}</p>
                </div>
                <flux:badge
                    color="{{ $resumenExpedientesGeneral['color'] }}"
                    size="lg"
                    >{{ $resumenExpedientesGeneral['label'] }}</flux:badge
                >
            </div>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">documento(s) registrados</p>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Registro</p>
            <p class="mt-3 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $inscripcionActual->fecha_inscripcion_ins?->format('d/m/Y') ?? 'N/A' }}
            </p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $inscripcionActual->fecha_inscripcion_ins?->format('h:i a') ?? 'Sin hora' }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:callout color="{{ $resumenPagoGeneral['color'] }}" icon="banknotes">
            <div class="space-y-1">
                <p class="text-sm font-medium">{{ $resumenPagoGeneral['label'] }}</p>
                <p class="text-sm">{{ $resumenPagoGeneral['detalle'] }}</p>
            </div>
        </flux:callout>

        <flux:callout color="{{ $resumenExpedientesGeneral['color'] }}" icon="document-text">
            <div class="space-y-1">
                <p class="text-sm font-medium">{{ $resumenExpedientesGeneral['label'] }}</p>
                <p class="text-sm">{{ $resumenExpedientesGeneral['detalle'] }}</p>
            </div>
        </flux:callout>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="space-y-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Informacion personal</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Datos del postulante</h3>
                    </div>
                    <flux:badge color="zinc" size="lg">
                        {{ $inscripcionActual->persona?->tipoDocumento?->nombre_tdo ?? 'DOC' }}
                    </flux:badge>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Numero de documento</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->numero_documento_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Correo electronico</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->correo_electronico_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Celular</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->persona?->celular_per ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Telefono</p>
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
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Direccion</p>
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

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Postulacion</p>
                <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Configuracion academica</h3>

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
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Lugar de inscripcion</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Turno</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->procesoOferta?->turno?->nombre_tur ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Convenio</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->convenioProceso?->convenio?->nombre_con ?? 'Sin convenio' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Ano de graduacion</p>
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
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
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
                            class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No hay pagos asociados a esta obligacion.</p>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-400"
                        >
                            Esta inscripcion no registra obligaciones de pago.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Expedientes</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Revision documental</h3>
                    </div>
                    <flux:badge color="zinc" size="lg">{{ $inscripcionActual->expedientes->count() }}</flux:badge>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($inscripcionActual->expedientes as $expediente)
                        <div
                            class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                                            <x-ui.icon name="arrow-square-out" size="3.5" />
                                            Abrir
                                        </a>
                                    @endif
                                </div>
                                <p>Observacion: {{ $expediente->observacion_iex ?: 'Sin observacion' }}</p>
                                <p>
                                    Revision: {{ $expediente->revisado_en?->format('d/m/Y h:i a') ?? 'No registrada' }}
                                    @if ($expediente->usuarioRevisor)
                                        | {{ $expediente->usuarioRevisor->nombres_usu ?? $expediente->usuarioRevisor->correo_usu }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/10 dark:bg-zinc-950/40 dark:text-zinc-400"
                        >
                            Esta inscripcion no tiene expedientes registrados.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
