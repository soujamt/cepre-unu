<div
    x-data="{
        segundosRestantes: 0,
        intervalo: null,
        get bloqueado() {
            return this.segundosRestantes > 0;
        },
        iniciarContador(segundos) {
            clearInterval(this.intervalo);
            this.segundosRestantes = segundos;
            this.intervalo = setInterval(() => {
                this.segundosRestantes--;
                if (this.segundosRestantes <= 0) {
                    clearInterval(this.intervalo);
                    this.segundosRestantes = 0;
                }
            }, 1000);
        },
    }"
    @rate-limited.window="iniciarContador($event.detail.segundos)"
    class="grid min-h-screen w-full lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]"
>
    <section class="flex min-h-screen flex-col px-6 py-8 sm:px-12 lg:px-16 xl:px-20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-11 items-center justify-center rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-7" />
                </div>
                <div class="leading-tight">
                    <p class="text-base font-semibold text-zinc-900 dark:text-white">CEPRE UNU</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Centro Preuniversitario</p>
                </div>
            </div>

            <flux:button
                x-data
                x-on:click="$flux.dark = !$flux.dark"
                variant="subtle"
                square
                aria-label="Cambiar tema"
            >
                <x-slot name="icon">
                    <x-ui.icon name="moon" size="5" class="text-zinc-500 dark:text-zinc-300" x-show="$flux.dark" x-cloak />
                    <x-ui.icon name="sun" size="5" class="text-zinc-500 dark:text-zinc-300" x-show="!$flux.dark" x-cloak />
                </x-slot>
            </flux:button>
        </div>

        <div class="flex flex-1 items-center py-10">
            <div class="mx-auto w-full max-w-sm">
                <div class="space-y-2">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Bienvenido de nuevo</p>
                    <h1 class="text-[1.75rem] leading-tight font-semibold tracking-tight text-zinc-900 dark:text-white">
                        Inicia sesión
                    </h1>
                    <p class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        Accede con tu cuenta institucional para continuar.
                    </p>
                </div>

                <div
                    x-show="bloqueado"
                    x-cloak
                    class="mt-6 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 dark:border-amber-400/20 dark:bg-amber-400/10"
                >
                    <x-ui.icon name="alert-triangle" size="5" class="mt-px text-amber-500" />
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Demasiados intentos. Intenta nuevamente en
                        <span class="font-semibold tabular-nums" x-text="segundosRestantes"></span>
                        segundos.
                    </p>
                </div>

                <form
                    class="mt-8 space-y-6"
                    wire:submit="login"
                    novalidate
                    :inert="bloqueado"
                    :class="{ 'opacity-55': bloqueado }"
                >
                    <div class="space-y-4">
                        <x-ui.input
                            name="correo"
                            label="Correo electrónico"
                            type="email"
                            wire:model="correo"
                            :invalid="$errors->has('correo')"
                            :error="$errors->first('correo')"
                            autocomplete="email"
                            iconLeading="envelope"
                            required
                            floating
                        />
                        <x-ui.input
                            name="password"
                            label="Contraseña"
                            type="password"
                            wire:model="password"
                            :invalid="$errors->has('password')"
                            :error="$errors->first('password')"
                            autocomplete="current-password"
                            iconLeading="lock"
                            required
                            floating
                        />
                    </div>

                    <flux:button
                        type="submit"
                        class="h-11 w-full font-medium"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="login"
                    >
                        <span wire:loading.remove wire:target="login">Ingresar a la plataforma</span>
                        <span wire:loading wire:target="login">Validando acceso...</span>
                    </flux:button>

                    <div class="flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-white/10"></span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="check-shield" size="4" class="text-index-500" />
                            Acceso seguro institucional
                        </span>
                        <span class="h-px flex-1 bg-zinc-200 dark:bg-white/10"></span>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
            <p>¿Problemas para ingresar? Comunícate con la oficina del CEPRE.</p>
            <p>© {{ date('Y') }} Universidad Nacional de Ucayali</p>
        </div>
    </section>

    <section class="relative hidden p-3 lg:block" aria-hidden="true">
        <div
            class="relative h-full overflow-hidden rounded-[1.75rem] bg-[linear-gradient(160deg,var(--color-index-400)_0%,var(--color-index-500)_45%,var(--color-index-700)_100%)] dark:bg-[linear-gradient(160deg,var(--color-index-800)_0%,var(--color-index-900)_50%,var(--color-index-950)_100%)]"
        >
            <div
                class="absolute inset-0 [background-image:linear-gradient(to_right,rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:40px_40px] [mask-image:radial-gradient(ellipse_at_center,black_35%,transparent_80%)]"
            ></div>
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-white/20 blur-3xl dark:bg-index-500/20"></div>
            <div class="absolute -bottom-32 -left-20 size-96 rounded-full bg-index-300/30 blur-3xl dark:bg-index-600/20"></div>

            <div class="absolute inset-x-10 top-[14%] xl:inset-x-16">
                {{-- Ventana del panel --}}
                <div
                    class="relative rounded-2xl border border-white/60 bg-white/95 p-4 shadow-[0_30px_60px_-20px_rgba(10,25,80,0.55)] dark:border-white/10 dark:bg-zinc-900/95"
                >
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-white/[0.06]">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-red-400"></span>
                            <span class="size-2.5 rounded-full bg-yellow-400"></span>
                            <span class="size-2.5 rounded-full bg-green-400"></span>
                            <span class="ms-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">Panel CEPRE · Inscripciones</span>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400"
                        >
                            <span class="size-1.5 rounded-full bg-green-500"></span>
                            Proceso activo
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-2.5">
                        @foreach ([
                            ['icon' => 'group', 'label' => 'Inscritos', 'valor' => '1 248', 'color' => 'text-index-500 bg-index-50 dark:bg-index-500/10'],
                            ['icon' => 'wallet', 'label' => 'Pagos validados', 'valor' => '1 102', 'color' => 'text-green-600 bg-green-50 dark:bg-green-500/10'],
                            ['icon' => 'folder-open', 'label' => 'Expedientes', 'valor' => '986', 'color' => 'text-purple-600 bg-purple-50 dark:bg-purple-500/10'],
                        ] as $indicador)
                            <div class="rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $indicador['label'] }}</span>
                                    <span class="flex size-5 items-center justify-center rounded-md {{ $indicador['color'] }}">
                                        <x-ui.icon :name="$indicador['icon']" size="3" />
                                    </span>
                                </div>
                                <p class="mt-1.5 text-lg font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">
                                    {{ $indicador['valor'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2.5 grid grid-cols-[1.1fr_1fr] gap-2.5">
                        <div class="rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Inscripciones por día</p>
                            <div class="mt-2 flex h-20 items-end gap-1.5">
                                @foreach ([35, 52, 44, 68, 58, 82, 74, 95, 66, 88] as $altura)
                                    <span
                                        class="flex-1 rounded-t-[3px] {{ $loop->index === 7 ? 'bg-index-500' : 'bg-index-100 dark:bg-index-500/25' }}"
                                        style="height: {{ $altura }}%"
                                    ></span>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-1.5 rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Últimas inscripciones</p>
                            @foreach ([
                                ['ancho' => 'w-20', 'estado' => 'Activo', 'color' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400'],
                                ['ancho' => 'w-16', 'estado' => 'Pagado', 'color' => 'bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300'],
                                ['ancho' => 'w-24', 'estado' => 'Revisión', 'color' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400'],
                            ] as $fila)
                                <div class="flex items-center gap-2">
                                    <span class="size-5 shrink-0 rounded-full bg-zinc-100 dark:bg-white/10"></span>
                                    <span class="h-1.5 {{ $fila['ancho'] }} rounded-full bg-zinc-200 dark:bg-white/15"></span>
                                    <span class="ms-auto rounded-full px-1.5 py-0.5 text-[9px] font-medium {{ $fila['color'] }}">
                                        {{ $fila['estado'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tarjeta flotante: pago validado --}}
                <div
                    class="absolute -bottom-10 -left-6 flex items-center gap-2.5 rounded-xl border border-white/60 bg-white p-2.5 pe-4 shadow-[0_20px_40px_-15px_rgba(10,25,80,0.5)] dark:border-white/10 dark:bg-zinc-900"
                >
                    <span class="flex size-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-500/10">
                        <x-ui.icon name="check-circle" size="5" />
                    </span>
                    <div class="leading-tight">
                        <p class="text-xs font-semibold text-zinc-900 dark:text-white">Pago validado</p>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Conciliación bancaria</p>
                    </div>
                </div>

                {{-- Tarjeta flotante: aulas asignadas --}}
                <div
                    class="absolute -top-6 -right-6 rounded-xl border border-white/60 bg-white p-3 shadow-[0_20px_40px_-15px_rgba(10,25,80,0.5)] dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="relative size-10 rounded-full bg-[conic-gradient(var(--color-index-500)_0_92%,var(--color-index-100)_92%_100%)] dark:bg-[conic-gradient(var(--color-index-400)_0_92%,rgba(255,255,255,0.1)_92%_100%)]"
                        >
                            <span class="absolute inset-1 flex items-center justify-center rounded-full bg-white text-[10px] font-semibold text-zinc-900 dark:bg-zinc-900 dark:text-white">92%</span>
                        </div>
                        <div class="leading-tight">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white">Aulas asignadas</p>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Turnos mañana y tarde</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute inset-x-6 bottom-6 xl:inset-x-8 xl:bottom-8">
                <div
                    class="rounded-2xl border border-white/20 bg-white/10 p-6 text-white backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04]"
                >
                    <p class="text-xs font-medium tracking-[0.18em] text-white/75 uppercase">Plataforma institucional</p>
                    <p class="mt-2 max-w-md text-2xl leading-snug font-semibold tracking-tight">
                        Gestión académica del proceso de admisión, en un solo lugar.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ([['icon' => 'clipboard-detail', 'texto' => 'Inscripciones'], ['icon' => 'wallet', 'texto' => 'Pagos'], ['icon' => 'school', 'texto' => 'Aulas'], ['icon' => 'bar-chart', 'texto' => 'Reportes']] as $modulo)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-medium"
                            >
                                <x-ui.icon :name="$modulo['icon']" size="4" />
                                {{ $modulo['texto'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
