@section ('icon')
    <x-header.icon icon="pencil-square" />
@endsection

@section ('header')
    <x-header.title>Nueva inscripción</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.index') }}" wire:navigate>Inscripciones</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.crear') }}" wire:navigate>
            Nueva inscripción</x-breadcrumbs.item
        >
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @if ($this->inicioInscripcionBloqueado)
        @php
            $contextoProceso = $this->contextoProcesoPostulacion;
        @endphp
        <div class="grid gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Disponibilidad</p>
                            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Nueva inscripción</h3>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                            <div class="flex items-start gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-amber-500 shadow-xs dark:bg-zinc-900 dark:text-amber-300">
                                    <x-ui.icon name="calendar-x" size="5" class="text-current" />
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Registro temporalmente no disponible</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $contextoProceso['mensaje'] }}</p>
                                </div>
                            </div>
                        </div>

                        <flux:button variant="filled" class="w-full font-normal" wire:click="volverListado">
                            <x-slot name="icon">
                                <x-ui.icon name="chevron-left-circle" size="5" class="text-current" />
                            </x-slot>
                            Volver al listado
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="relative border-b border-zinc-200/80 px-6 py-5 dark:border-white/10">
                        <div class="absolute inset-y-0 right-0 w-40 bg-linear-to-l from-amber-100/80 to-transparent dark:from-amber-500/10"></div>
                        <div class="relative space-y-2">
                            <p class="text-xs font-semibold tracking-[0.22em] text-zinc-500 uppercase dark:text-zinc-400">Control de vigencia</p>
                            <h3 class="max-w-2xl text-xl font-semibold text-zinc-900 dark:text-white">El formulario se habilita solo cuando exista un proceso activo y listo para inscripciones.</h3>
                            <p class="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">Así evitamos que el usuario complete datos y recién más adelante descubra que no puede continuar con la postulación.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 px-6 py-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.9fr)]">
                        <div class="space-y-4">
                            <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $contextoProceso['mensaje'] }}">
                                <p class="text-sm">Cuando se habilite un proceso vigente con ofertas activas, este formulario volverá a estar disponible automáticamente.</p>
                            </flux:callout>

                            <div class="space-y-2">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Requisitos para habilitar</p>

                                <ul
                                    class="divide-y divide-zinc-200/80 rounded-xl border border-zinc-200/80 bg-zinc-50/60 dark:divide-white/[0.06] dark:border-white/[0.06] dark:bg-zinc-950/40"
                                >
                                    @foreach ([
                                        ['icono' => 'calendar', 'titulo' => 'Proceso', 'texto' => 'Debe estar habilitado y con la fecha actual dentro de su periodo de inscripción.'],
                                        ['icono' => 'layers', 'titulo' => 'Ofertas', 'texto' => 'Se requiere al menos una oferta activa para continuar con la postulación.'],
                                    ] as $requisito)
                                        <li class="flex items-start gap-3 px-4 py-3">
                                            <span
                                                class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200/80 bg-white text-zinc-500 shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:text-zinc-400 dark:shadow-none"
                                            >
                                                <x-ui.icon :name="$requisito['icono']" size="4" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $requisito['titulo'] }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $requisito['texto'] }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-white/[0.06] dark:bg-zinc-950/40">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Qué revisar</p>
                                    <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Checklist rápida</h4>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-white/80 p-3 dark:border-white/[0.06] dark:bg-zinc-900/60 shadow-card">
                                        <x-ui.icon name="calendar-check" size="5" class="mt-0.5 text-emerald-500" />
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Verifique que las fechas de inicio y fin del proceso incluyan el día actual.</p>
                                    </div>

                                    <div class="flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-white/80 p-3 dark:border-white/[0.06] dark:bg-zinc-900/60 shadow-card">
                                        <x-ui.icon name="toggle-left" size="5" class="mt-0.5 text-sky-500" />
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Confirme que el proceso no esté deshabilitado o cerrado automáticamente por vigencia.</p>
                                    </div>

                                    <div class="flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-white/80 p-3 dark:border-white/[0.06] dark:bg-zinc-900/60 shadow-card">
                                        <x-ui.icon name="layers" size="5" class="mt-0.5 text-violet-500" />
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Revise que el proceso tenga ofertas asignadas y habilitadas para inscripción.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="grid gap-5 xl:grid-cols-[19rem_minmax(0,1fr)]">
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso de registro</p>
                            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Formulario por pasos</h3>
                        </div>

                        <div class="relative">
                            <div class="absolute top-5 bottom-5 left-5 w-px bg-zinc-200 dark:bg-white/10"></div>

                            <div class="relative space-y-1">
                                @foreach ($this->pasos as $indice => $paso)
                                    @php
                                        $esActivo = $pasoActual === $indice;
                                        $esCompletado = $indice < $pasoActual && match ($indice) {
                                            1 => $personaId !== null,
                                            2 => filled($generalForm['anio_graduacion']),
                                            3 => $inscripcionId !== null,
                                            4 => $this->tienePagoValidadoWizard,
                                            default => false,
                                        };
                                        $esPendiente = ! $esActivo && ! $esCompletado;
                                    @endphp
                                    <button
                                        type="button"
                                        wire:key="paso-inscripcion-{{ $indice }}"
                                        wire:click="irPaso({{ $indice }})"
                                        @class ([
                                            'group relative w-full rounded-xl p-2.5 text-left transition',
                                            'bg-yellow-500 shadow-sm dark:bg-yellow-500' => $esActivo,
                                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => ! $esActivo,
                                        ])
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                @class ([
                                                    'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full transition',
                                                    'bg-white text-yellow-500 shadow-sm dark:bg-zinc-900 dark:text-yellow-400' => $esActivo,
                                                    'bg-lime-500 text-white' => $esCompletado,
                                                    'border border-zinc-200 bg-white text-zinc-400 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-500' => $esPendiente,
                                                ])
                                            >
                                                @if ($esCompletado)
                                                    <x-ui.icon name="check" size="5" class="text-current" />
                                                @else
                                                    <x-ui.icon :name="$paso['icono']" size="5" class="text-current" />
                                                @endif
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    @class ([
                                                        'truncate text-sm font-medium',
                                                        'text-white dark:text-zinc-900' => $esActivo,
                                                        'text-zinc-800 dark:text-zinc-100' => $esCompletado,
                                                        'text-zinc-500 dark:text-zinc-400' => $esPendiente,
                                                    ])
                                                >
                                                    {{ $paso['titulo'] }}
                                                </p>
                                                <p
                                                    @class ([
                                                        'truncate text-xs',
                                                        'text-zinc-600 dark:text-yellow-900' => $esActivo,
                                                        'text-zinc-600 dark:text-zinc-300' => $esCompletado,
                                                        'text-zinc-400 dark:text-zinc-500' => $esPendiente,
                                                    ])
                                                >
                                                    {{ $paso['descripcion'] }}
                                                </p>
                                            </div>

                                            @if ($esActivo)
                                                <span class="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-white uppercase dark:bg-zinc-900/30 dark:text-zinc-900">
                                                    Actual
                                                </span>
                                            @elseif ($esCompletado)
                                                <span class="shrink-0 rounded-full bg-lime-50 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-lime-600 uppercase dark:bg-lime-500/10 dark:text-lime-400">
                                                    Listo
                                                </span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <flux:button variant="filled" class="w-full font-normal" wire:click="volverListado">
                            <x-slot name="icon">
                                <x-ui.icon name="chevron-left-circle" size="5" class="text-current" />
                            </x-slot>
                            Volver al listado
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                @if ($pasoActual === 1)
                    @include('pages::inscripciones.⚡inscripcion-crear.pasos.personal')
                @elseif ($pasoActual === 2)
                    @include('pages::inscripciones.⚡inscripcion-crear.pasos.general')
                @elseif ($pasoActual === 3)
                    @include('pages::inscripciones.⚡inscripcion-crear.pasos.postulacion')
                @elseif ($pasoActual === 4)
                    @include('pages::inscripciones.⚡inscripcion-crear.pasos.pago')
                @elseif ($pasoActual === 5)
                    @include('pages::inscripciones.⚡inscripcion-crear.pasos.expedientes')
                @else
                <div
                    class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
                >
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="layers" size="14" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ $this->pasos[$pasoActual]['titulo'] }}
                        </p>
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">Este paso se habilitará en la siguiente iteración. La base del wizard ya quedó lista para continuar sin perder el avance anterior.</p>

                        <div class="flex gap-3">
                            <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                                Volver al paso anterior
                            </flux:button>

                            <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                                Volver al listado
                            </flux:button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
