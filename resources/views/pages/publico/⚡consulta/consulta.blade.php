<div class="min-h-screen bg-zinc-50 pb-16 dark:bg-zinc-950">
    <x-publico.encabezado
        titulo="Consultar mi inscripción"
        subtitulo="Ingrese su DNI y el número de operación de cualquiera de sus pagos."
    />

    <main class="mx-auto max-w-5xl space-y-5 px-5 pt-4">
        <form wire:submit="consultar" class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
            <div class="grid gap-4 sm:grid-cols-12">
                <div class="sm:col-span-5">
                    <x-ui.input
                        label="DNI / CE"
                        name="documento"
                        inputmode="numeric"
                        maxlength="12"
                        autocomplete="off"
                        wire:model="documento"
                        :invalid="$errors->has('documento')"
                        :error="$errors->first('documento')"
                        required
                        floating
                    />
                </div>
                <div class="sm:col-span-4">
                    <x-ui.input
                        label="N° de operación"
                        name="operacion"
                        inputmode="numeric"
                        maxlength="20"
                        autocomplete="off"
                        wire:model="operacion"
                        :invalid="$errors->has('operacion')"
                        :error="$errors->first('operacion')"
                        required
                        floating
                    />
                </div>
                <div class="sm:col-span-3">
                    <flux:button type="submit" variant="primary" class="w-full font-normal">
                        <x-slot name="icon">
                            <x-ui.icon name="search" size="5" class="text-white" />
                        </x-slot>
                        Consultar
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($inscripcion = $this->inscripcion)
            @php
                $obligacion = $inscripcion->obligacionesPago->sortByDesc('id_iop')->first();
                $aula = $inscripcion->procesoAula?->aula;
            @endphp
            <section class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-card dark:border-white/[0.06] dark:bg-zinc-900" aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Código {{ $inscripcion->codigo_ins }}</p>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ trim($inscripcion->persona?->nombres_per.' '.$inscripcion->persona?->apellido_paterno_per.' '.$inscripcion->persona?->apellido_materno_per) }}
                        </h2>
                    </div>
                    <flux:badge color="{{ $inscripcion->estado_ins->getColor() }}">{{ $inscripcion->estado_ins->getNombre() }}</flux:badge>
                </div>

                <div class="mt-5 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Su aula</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">{{ $aula ? $aula->nombre_aul : 'Por asignar' }}</p>
                    @if ($aula)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $aula->piso_aul?->getNombre() }} · {{ $aula->lugar_aul?->getNombre() }}</p>
                    @endif
                </div>

                <dl class="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Proceso' => $inscripcion->proceso?->nombre_pro,
                        'Sede' => $inscripcion->sede?->nombre_sed,
                        'Modalidad' => $inscripcion->modalidadAdmision?->nombre_mad,
                        'Carrera' => $inscripcion->carrera?->nombre_car,
                        'Turno' => $inscripcion->turno?->descripcionConHorario(),
                        'Pagado' => $obligacion ? 'S/ '.number_format((float) $obligacion->monto_pagado_iop, 2).' de S/ '.number_format((float) $obligacion->monto_total_iop, 2) : null,
                    ] as $etiqueta => $valor)
                        <div class="min-w-0">
                            <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $etiqueta }}</dt>
                            <dd class="text-sm font-medium break-words text-zinc-800 tabular-nums dark:text-zinc-100">{{ $valor ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($enlaces = $this->enlaces)
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
                    </div>
                @endif
            </section>
        @endif

        <flux:button variant="ghost" href="{{ route('publico.portal') }}" wire:navigate class="font-normal">
            <x-slot name="icon">
                <x-ui.icon name="arrow-left" size="5" />
            </x-slot>
            Volver al portal
        </flux:button>
    </main>
</div>
