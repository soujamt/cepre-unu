<div class="min-h-screen bg-zinc-50 pb-16 dark:bg-zinc-950">
    <x-publico.encabezado
        titulo="Inscripción en línea"
        subtitulo="Necesita el DNI del postulante y el número de operación de su pago en el banco."
    />

    <main class="mx-auto max-w-5xl space-y-5 px-5 pt-4">
        @if ($registrada = $this->inscripcionRegistrada)
            @php
                $enlaces = $this->enlacesDocumentos;
                $activa = $registrada->estado_ins === App\Enums\EstadoInscripcion::Activo;
            @endphp
            <section class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                        <x-ui.icon name="check-circle" size="6" />
                    </span>
                    <div class="min-w-0 space-y-1">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Inscripción registrada</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">
                            Código <span class="font-semibold tabular-nums">{{ $registrada->codigo_ins }}</span>.
                            {{ $activa ? 'Su inscripción está ACTIVA.' : 'Su inscripción queda PENDIENTE hasta completar el pago.' }}
                        </p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'Proceso' => $registrada->proceso?->nombre_pro,
                        'Carrera' => $registrada->carrera?->nombre_car,
                        'Turno' => $registrada->turno?->descripcionConHorario(),
                        'Aula' => $registrada->procesoAula?->aula ? $registrada->procesoAula->aula->nombre_aul.' · '.$registrada->procesoAula->aula->piso_aul?->getNombre() : 'Por asignar',
                    ] as $etiqueta => $valor)
                        <div class="min-w-0">
                            <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $etiqueta }}</dt>
                            <dd class="text-sm font-medium break-words text-zinc-800 dark:text-zinc-100">{{ $valor ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 flex flex-wrap gap-3">
                    <flux:button variant="primary" href="{{ $enlaces['ficha'] }}" target="_blank">
                        <x-slot name="icon">
                            <x-ui.icon name="file" size="5" class="text-white" />
                        </x-slot>
                        Descargar ficha
                    </flux:button>
                    @if ($enlaces['carnet'])
                        <flux:button href="{{ $enlaces['carnet'] }}" target="_blank">
                            <x-slot name="icon">
                                <x-ui.icon name="id-card" size="5" />
                            </x-slot>
                            Descargar carnet
                        </flux:button>
                    @endif
                    <flux:button variant="ghost" href="{{ route('publico.portal') }}" wire:navigate>Salir</flux:button>
                </div>
                <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">Imprima la ficha, fírmela y preséntela en el CEPRE-UNU. Puede volver a descargarla desde “Consultar mi inscripción”.</p>
            </section>
        @elseif ($this->inicioInscripcionBloqueado)
            <flux:callout variant="warning" icon="exclamation-circle" heading="La inscripción en línea no está disponible">
                <p class="text-sm">{{ $this->contextoProcesoPostulacion['mensaje'] }}</p>
            </flux:callout>
            <flux:button variant="filled" href="{{ route('publico.portal') }}" wire:navigate>Volver</flux:button>
        @else
            <ol class="grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Pasos de la inscripción">
                @foreach ($this->pasos as $indice => $paso)
                    <li
                        wire:key="paso-publico-{{ $indice }}"
                        @if ($pasoActual === $indice) aria-current="step" @endif
                        @class ([
                            'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm',
                            'border-index-300 bg-index-50 text-index-700 dark:border-index-500/30 dark:bg-index-500/10 dark:text-index-200' => $pasoActual === $indice,
                            'border-zinc-200/80 bg-white text-zinc-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-200' => $indice < $pasoActual,
                            'border-zinc-200/80 bg-white text-zinc-400 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-500' => $indice > $pasoActual,
                        ])
                    >
                        <span @class ([
                            'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-index-500 text-white' => $pasoActual === $indice,
                            'bg-green-500 text-white' => $indice < $pasoActual,
                            'border border-zinc-300 dark:border-white/15' => $indice > $pasoActual,
                        ])>
                            @if ($indice < $pasoActual)
                                <x-ui.icon name="check" size="4" />
                            @else
                                {{ $indice }}
                            @endif
                        </span>
                        <span class="truncate font-medium">{{ $paso['titulo'] }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($pasoActual === 1)
                @include('pages::inscripciones.⚡inscripcion-crear.pasos.pago')
            @elseif ($pasoActual === 2)
                @include('pages::inscripciones.⚡inscripcion-crear.pasos.personal')
            @elseif ($pasoActual === 3)
                @include('pages::inscripciones.⚡inscripcion-crear.pasos.general')
            @else
                @include('pages::inscripciones.⚡inscripcion-crear.pasos.postulacion')
            @endif
        @endif
    </main>
</div>
