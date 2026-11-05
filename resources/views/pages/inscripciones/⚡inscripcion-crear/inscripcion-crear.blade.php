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
        @php
            $estadoPasos = collect($this->pasos)->mapWithKeys(fn (array $paso, int $indice): array => [
                $indice => match (true) {
                    $pasoActual === $indice => 'activo',
                    $indice < $pasoActual && match ($indice) {
                        1 => $personaId !== null,
                        2 => filled($generalForm['anio_graduacion']),
                        3 => $inscripcionId !== null,
                        4 => $this->tienePagoValidadoWizard,
                        default => false,
                    } => 'completado',
                    default => 'pendiente',
                },
            ]);
            $totalPasos = $estadoPasos->count();
            $pasosCompletados = $estadoPasos->filter(fn (string $estado): bool => $estado === 'completado')->count();
            $porcentajeAvance = $totalPasos > 0 ? (int) round($pasosCompletados * 100 / $totalPasos) : 0;
        @endphp

        <div class="grid gap-5 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <div class="space-y-4 xl:sticky xl:top-0 xl:self-start">
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="space-y-5">
                        <div class="space-y-3">
                            <div class="flex items-baseline justify-between gap-3">
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Nueva inscripción</p>
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Paso {{ $pasoActual }} de {{ $totalPasos }}</h3>
                                </div>
                                <span class="text-sm font-semibold text-index-600 tabular-nums dark:text-index-300">{{ $porcentajeAvance }}%</span>
                            </div>

                            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentajeAvance }}">
                                <div class="h-full rounded-full bg-index-500 transition-all duration-500" style="width: {{ $porcentajeAvance }}%"></div>
                            </div>
                        </div>

                        <ol class="space-y-1">
                            @foreach ($this->pasos as $indice => $paso)
                                @php
                                    $estado = $estadoPasos[$indice];
                                @endphp
                                <li wire:key="paso-inscripcion-{{ $indice }}" class="relative">
                                    @unless ($loop->last)
                                        <span
                                            aria-hidden="true"
                                            @class ([
                                                'absolute top-11 -bottom-1 left-[1.375rem] w-0.5 rounded-full',
                                                'bg-green-500/70' => $estado === 'completado',
                                                'bg-zinc-200 dark:bg-white/10' => $estado !== 'completado',
                                            ])
                                        ></span>
                                    @endunless

                                    <button
                                        type="button"
                                        wire:click="irPaso({{ $indice }})"
                                        @if ($estado === 'activo') aria-current="step" @endif
                                        @class ([
                                            'flex w-full items-start gap-3 rounded-lg p-2 text-left transition-colors',
                                            'bg-index-50 dark:bg-index-500/10' => $estado === 'activo',
                                            'hover:bg-zinc-50 dark:hover:bg-white/[0.04]' => $estado !== 'activo',
                                        ])
                                    >
                                        <span
                                            @class ([
                                                'relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition',
                                                'bg-index-500 text-white ring-4 ring-index-500/15' => $estado === 'activo',
                                                'bg-green-500 text-white' => $estado === 'completado',
                                                'border border-zinc-300 bg-white text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400' => $estado === 'pendiente',
                                            ])
                                        >
                                            @if ($estado === 'completado')
                                                <x-ui.icon name="check" size="4" class="text-current" />
                                            @else
                                                {{ $indice }}
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1 pt-0.5">
                                            <span
                                                @class ([
                                                    'block text-sm leading-5 font-medium',
                                                    'text-index-700 dark:text-index-200' => $estado === 'activo',
                                                    'text-zinc-800 dark:text-zinc-100' => $estado === 'completado',
                                                    'text-zinc-500 dark:text-zinc-400' => $estado === 'pendiente',
                                                ])
                                            >
                                                {{ $paso['titulo'] }}
                                            </span>
                                            <span
                                                @class ([
                                                    'block text-xs leading-4',
                                                    'text-index-600/80 dark:text-index-300/80' => $estado === 'activo',
                                                    'text-zinc-500 dark:text-zinc-400' => $estado === 'completado',
                                                    'text-zinc-400 dark:text-zinc-500' => $estado === 'pendiente',
                                                ])
                                            >
                                                {{ $estado === 'completado' ? 'Completado' : $paso['descripcion'] }}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>

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
