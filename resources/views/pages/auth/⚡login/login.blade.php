<div
    class="text-card-foreground relative w-full max-w-md overflow-hidden rounded-[1.75rem] border border-zinc-200/70 bg-white/95 shadow-xl ring-1 shadow-zinc-950/5 ring-white/70 backdrop-blur-sm dark:border-white/10 dark:bg-zinc-900/90 dark:shadow-black/30 dark:ring-white/5"
>
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-36 bg-[linear-gradient(180deg,rgba(132,204,22,0.10),transparent)] dark:bg-[linear-gradient(180deg,rgba(163,230,53,0.12),transparent)]"
    ></div>
    <div
        class="pointer-events-none absolute inset-x-8 top-0 h-px bg-linear-to-r from-transparent via-lime-500/60 to-transparent dark:via-lime-400/50"
    ></div>

    <div class="relative flex flex-col gap-8 px-6 py-7 sm:px-7 sm:py-8">
        <div class="flex flex-col items-center gap-5 text-center">
            <div
                class="inline-flex items-center gap-3 rounded-full border border-lime-300 bg-lime-100 px-4 py-2 text-[11px] font-semibold tracking-[0.24em] text-lime-700 uppercase dark:border-lime-400/15 dark:bg-lime-400/8 dark:text-lime-300"
            >
                <span class="size-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                Acceso institucional
            </div>

            <div class="flex flex-col items-center gap-4">
                <div
                    class="flex size-20 items-center justify-center rounded-3xl border border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/5 dark:border-white/10 dark:bg-zinc-950/70 dark:shadow-black/20"
                >
                    <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-12" />
                </div>

                <div class="space-y-2">
                    <p class="text-[11px] font-semibold tracking-[3px] text-zinc-500 uppercase dark:text-zinc-400">Centro Preuniversitario - UNU</p>
                    <div class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-[2rem] dark:text-white">
                        Inicia sesión
                    </div>
                    <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500 dark:text-zinc-400">Accede con tus credenciales para continuar en la plataforma institucional.</p>
                </div>
            </div>
        </div>

        <form class="space-y-5" wire:submit="login" novalidate>
            <x-ui.input
                name="correo"
                label="Correo electrónico"
                type="email"
                wire:model="correo"
                :invalid="$errors->has('correo')"
                :error="$errors->first('correo')"
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
                required
                floating
            />

            <div class="space-y-3 pt-1">
                <flux:button type="submit" class="w-full" variant="primary">
                    Ingresar
                </flux:button>

                <p class="text-center text-xs leading-5 text-zinc-500 dark:text-zinc-400">Plataforma de acceso para procesos académicos, inscripciones y seguimiento institucional.</p>
            </div>
        </form>
    </div>
</div>
