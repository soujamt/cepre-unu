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
    class="relative grid w-full max-w-6xl overflow-hidden rounded-4xl border border-white/70 bg-white/78 shadow-[0_30px_90px_rgba(15,23,42,0.16)] ring-1 ring-zinc-200/80 backdrop-blur-xl lg:grid-cols-[1.05fr_minmax(420px,480px)] dark:border-white/10 dark:bg-zinc-950/72 dark:shadow-[0_30px_90px_rgba(0,0,0,0.45)] dark:ring-white/8"
>
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[linear-gradient(180deg,rgba(132,204,22,0.10),transparent)] dark:bg-[linear-gradient(180deg,rgba(163,230,53,0.12),transparent)]"
    ></div>
    <div
        class="pointer-events-none absolute inset-x-8 top-0 h-px bg-linear-to-r from-transparent via-lime-500/60 to-transparent dark:via-lime-400/50"
    ></div>
    <div
        class="pointer-events-none absolute inset-y-0 left-[58%] hidden w-px bg-linear-to-b from-transparent via-zinc-200 to-transparent lg:block dark:via-white/10"
    ></div>

    <section
        class="relative hidden overflow-hidden bg-[linear-gradient(150deg,rgba(255,255,255,0.92),rgba(241,245,249,0.76))] px-8 py-8 lg:flex lg:flex-col lg:justify-between dark:bg-[linear-gradient(160deg,rgba(24,24,27,0.82),rgba(9,9,11,0.88))]"
    >
        <div
            class="pointer-events-none absolute top-8 -left-16 h-56 w-56 rounded-full bg-lime-300/35 blur-3xl dark:bg-lime-400/12"
        ></div>
        <div
            class="pointer-events-none absolute right-0 bottom-0 h-64 w-64 rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-400/10"
        ></div>

        <div class="relative flex flex-col gap-8">
            <div
                class="inline-flex w-fit items-center gap-3 rounded-full border border-lime-300/80 bg-lime-100/90 px-4 py-2 text-[11px] font-semibold tracking-[0.24em] text-lime-700 uppercase dark:border-lime-400/15 dark:bg-lime-400/8 dark:text-lime-300"
            >
                <span class="size-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                Acceso institucional
            </div>

            <div class="max-w-xl space-y-8">
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-18 items-center justify-center rounded-3xl border border-zinc-200/80 bg-white/90 shadow-sm shadow-zinc-950/5 dark:border-white/10 dark:bg-zinc-950/70 dark:shadow-black/20"
                    >
                        <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-11" />
                    </div>

                    <div class="space-y-1">
                        <p class="text-[11px] font-semibold tracking-[0.28em] text-zinc-500 uppercase dark:text-zinc-400">Centro Preuniversitario</p>
                        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">CEPRE UNU</h1>
                    </div>
                </div>

                <div class="space-y-4">
                    <p class="max-w-md text-4xl leading-tight font-semibold tracking-tight text-zinc-950 dark:text-white">Plataforma institucional para la gestión académica.</p>
                    <p class="max-w-md text-sm leading-7 text-zinc-600 dark:text-zinc-400">Un acceso simple, claro y enfocado en continuar tu trabajo.</p>
                </div>

                <div
                    class="relative overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/78 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] dark:border-white/10 dark:bg-white/6 dark:shadow-[0_20px_50px_rgba(0,0,0,0.22)]"
                >
                    <div
                        class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[linear-gradient(180deg,rgba(132,204,22,0.12),transparent)] dark:bg-[linear-gradient(180deg,rgba(163,230,53,0.10),transparent)]"
                    ></div>
                    <div class="relative space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-[11px] font-semibold tracking-[0.2em] text-zinc-500 uppercase dark:text-zinc-400">Vista general</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">Panel institucional</p>
                            </div>

                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-lime-200 bg-lime-50 px-3 py-1.5 text-[11px] font-medium text-lime-700 dark:border-lime-400/20 dark:bg-lime-400/8 dark:text-lime-300"
                            >
                                <span class="size-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                                En línea
                            </div>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-[1.35fr_0.95fr]">
                            <div
                                class="space-y-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/45"
                            >
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Actividad</p>
                                        <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">78%</p>
                                    </div>
                                    <div class="flex items-end gap-1.5">
                                        <span class="h-8 w-2 rounded-full bg-lime-200 dark:bg-lime-400/20"></span>
                                        <span class="h-12 w-2 rounded-full bg-lime-300 dark:bg-lime-400/35"></span>
                                        <span class="h-16 w-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                                        <span class="h-10 w-2 rounded-full bg-lime-300 dark:bg-lime-400/35"></span>
                                        <span class="h-14 w-2 rounded-full bg-lime-400 dark:bg-lime-300/80"></span>
                                        <span class="h-9 w-2 rounded-full bg-lime-200 dark:bg-lime-400/20"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div
                                        class="min-w-0 rounded-xl bg-white px-3 py-3 shadow-sm shadow-zinc-950/5 dark:bg-white/6"
                                    >
                                        <div class="h-4 w-full rounded bg-zinc-500/20 dark:bg-zinc-400/50"></div>
                                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">+24</p>
                                    </div>
                                    <div
                                        class="min-w-0 rounded-xl bg-white px-3 py-3 shadow-sm shadow-zinc-950/5 dark:bg-white/6"
                                    >
                                        <div class="h-4 w-full rounded bg-zinc-500/20 dark:bg-zinc-400/50"></div>
                                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">+18</p>
                                    </div>
                                    <div
                                        class="min-w-0 rounded-xl bg-white px-3 py-3 shadow-sm shadow-zinc-950/5 dark:bg-white/6"
                                    >
                                        <div class="h-4 w-full rounded bg-zinc-500/20 dark:bg-zinc-400/50"></div>
                                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">+12</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="space-y-3 rounded-2xl border border-zinc-200/80 bg-white/85 p-4 dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Módulos</p>
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Hoy</span>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center gap-3 rounded-xl bg-zinc-50 px-3 py-3 dark:bg-zinc-950/50"
                                    >
                                        <span class="size-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">Inscripciones</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Actividad estable</p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-3 rounded-xl bg-zinc-50 px-3 py-3 dark:bg-zinc-950/50"
                                    >
                                        <span class="size-2 rounded-full bg-sky-500 dark:bg-sky-400"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">Pagos</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Flujo normal</p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-3 rounded-xl bg-zinc-50 px-3 py-3 dark:bg-zinc-950/50"
                                    >
                                        <span class="size-2 rounded-full bg-violet-500 dark:bg-violet-400"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">Reportes</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Actualizados</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative flex flex-col justify-center px-6 py-7 sm:px-8 sm:py-9 lg:px-10">
        <div class="mx-auto flex w-full max-w-md flex-col gap-7">
            <div class="flex flex-col items-center gap-5 text-center lg:hidden">
                <div
                    class="inline-flex items-center gap-3 rounded-full border border-lime-300 bg-lime-100 px-4 py-2 text-[11px] font-semibold tracking-[0.24em] text-lime-700 uppercase dark:border-lime-400/15 dark:bg-lime-400/8 dark:text-lime-300"
                >
                    <span class="size-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                    Acceso institucional
                </div>

                <div
                    class="flex size-18 items-center justify-center rounded-3xl border border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/5 dark:border-white/10 dark:bg-zinc-950/70 dark:shadow-black/20"
                >
                    <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-11" />
                </div>
            </div>

            <div class="space-y-3 text-center lg:text-left">
                <div class="space-y-2">
                    <p class="text-[11px] font-semibold tracking-[0.24em] text-zinc-500 uppercase dark:text-zinc-400">Bienvenido de nuevo</p>
                    <h2 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">Inicia sesión</h2>
                </div>
                <p class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">Accede con tu cuenta institucional.</p>
            </div>

            <div
                x-show="bloqueado"
                x-cloak
                class="rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-3 dark:border-amber-400/20 dark:bg-amber-400/8"
            >
                <p class="text-center text-sm font-medium text-amber-700 dark:text-amber-400">Demasiados intentos. Intenta nuevamente en
                <span class="font-bold tabular-nums" x-text="segundosRestantes"></span>
                segundos.</p>
            </div>

            <form
                class="space-y-5"
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
                        iconLeading="at"
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

                <div class="space-y-3 pt-1">
                    <flux:button
                        type="submit"
                        class="w-full font-medium"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="login"
                    >
                        <span wire:loading.remove wire:target="login">Ingresar a la plataforma</span>
                        <span wire:loading wire:target="login">Validando acceso...</span>
                    </flux:button>

                    <div
                        class="flex items-center justify-center gap-2 text-xs text-zinc-500 lg:justify-start dark:text-zinc-400"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            Acceso seguro CEPRE UNU
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
