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
                    @if ($inscripcionActual->enRevision())
                        <flux:badge color="amber">EN REVISIÓN · PORTAL</flux:badge>
                    @endif
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

            <div class="flex shrink-0 flex-wrap gap-2">
                @if ($inscripcionActual->enRevision())
                    @permiso ('inscripcion_estado')
                        <flux:button variant="primary" wire:click="aprobarRevision">
                            <x-slot name="icon">
                                <x-ui.icon name="check-circle" size="5" class="text-white" />
                            </x-slot>
                            Aprobar inscripción
                        </flux:button>
                    @endpermiso
                @endif
                @if ($inscripcionActual->estado_ins !== App\Enums\EstadoInscripcion::Anulado)
                    <flux:button href="{{ route('inscripciones.ficha', encode($inscripcionActual->id_ins)) }}" target="_blank">
                        <x-slot name="icon">
                            <x-ui.icon name="file" size="5" />
                        </x-slot>
                        Ver ficha
                    </flux:button>
                @endif
                @if ($inscripcionActual->estado_ins === App\Enums\EstadoInscripcion::Activo)
                    <flux:button href="{{ route('inscripciones.carnet', encode($inscripcionActual->id_ins)) }}" target="_blank">
                        <x-slot name="icon">
                            <x-ui.icon name="user-id-card" size="5" />
                        </x-slot>
                        Ver carnet
                    </flux:button>
                @endif
                <flux:button wire:click="volverListado">
                    <x-slot name="icon">
                        <x-ui.icon name="arrow-left" size="5" />
                    </x-slot>
                    Volver al listado
                </flux:button>
            </div>
        </div>
    </div>

    <x-resumen>
        <x-resumen.item
            label="Proceso"
            :value="$inscripcionActual->proceso?->nombre_pro ?? 'SIN PROCESO'"
            icon="layers"
            tono="info"
            detalle-icon="calendar"
            :detalle="($inscripcionActual->proceso?->fecha_inicio_pro?->format('d/m/Y h:i a') ?? 'Sin fechas').' al '.($inscripcionActual->proceso?->fecha_fin_pro?->format('d/m/Y h:i a') ?? '—')"
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
            label="Estado"
            :value="$inscripcionActual->estado_ins->getNombre()"
            :icon="$inscripcionActual->estado_ins->getIcon()"
            :tono="$tonoPorColor($inscripcionActual->estado_ins->getColor())"
            detalle-icon="wallet"
            :detalle="$inscripcionActual->estado_ins === \App\Enums\EstadoInscripcion::Activo ? 'Pago validado' : 'Falta validar el pago'"
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
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Apoderado</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $inscripcionActual->persona?->nombre_apoderado_per ?? 'No registrado' }}
                            @if ($inscripcionActual->persona?->celular_apoderado_per)
                                <span class="block text-xs font-normal text-zinc-500 tabular-nums dark:text-zinc-400">{{ $inscripcionActual->persona->celular_apoderado_per }}</span>
                            @endif
                        </p>
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
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->carrera?->nombre_car ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Sede</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->sede?->nombre_sed ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Modalidad</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->modalidadAdmision?->nombre_mad ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Turno</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $inscripcionActual->turno?->nombre_tur ?? 'No registrado' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Aula asignada</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $this->aulaResumen() }}</p>
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
        </div>
    </div>
</div>
