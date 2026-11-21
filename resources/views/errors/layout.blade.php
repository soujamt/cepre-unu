<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield ('code') | @yield ('title') - CEPRE UNU</title>

    <meta
        name="description"
        content="Plataforma institucional del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU"
    />
    <meta name="author" content="CEPRE - UNU" />

    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />

    @include ('partials.tema')
    @vite (['resources/css/inertia.css'])
</head>
<body class="h-full w-full bg-zinc-100 font-sans text-base text-zinc-800 antialiased dark:bg-[#070708] dark:text-zinc-100">
    <div class="flex h-dvh items-center justify-center p-4">
        <div
            class="mx-auto w-full max-w-lg rounded-3xl border border-zinc-200/80 bg-white p-8 text-center shadow-xs sm:p-10 dark:border-white/[0.07] dark:bg-white/[0.03]"
        >
            <div
                class="mx-auto mb-6 flex size-16 items-center justify-center rounded-2xl border border-index-100 bg-index-50 text-index-500 dark:border-index-500/20 dark:bg-index-500/10 dark:text-index-300"
            >
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    @switch (trim($__env->yieldContent('icono')))
                        @case ('candado')
                            <rect x="4" y="11" width="16" height="10" rx="2.5" />
                            <path d="M8 11V7.5a4 4 0 0 1 8 0V11" />
                            @break
                        @case ('pregunta')
                            <circle cx="12" cy="12" r="9" />
                            <path d="M9.5 9.5a2.5 2.5 0 0 1 4.9.7c0 1.7-2.4 2.3-2.4 3.8" />
                            <path d="M12 17h.01" />
                            @break
                        @case ('reloj')
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7.5V12l3 2" />
                            @break
                        @case ('cerrado')
                            <circle cx="12" cy="12" r="9" />
                            <path d="m15 9-6 6M9 9l6 6" />
                            @break
                        @default
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 8v4.5M12 16h.01" />
                    @endswitch
                </svg>
            </div>

            <p class="text-sm font-semibold tracking-[0.2em] text-index-500 dark:text-index-400">
                ERROR @yield ('code')
            </p>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                @yield ('title')
            </h1>

            <p class="mt-3 mb-8 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                @yield ('message')
            </p>

            <a
                href="/"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-index-500 px-5 text-sm font-medium text-white shadow-xs transition-colors hover:bg-index-600 focus-visible:ring-3 focus-visible:ring-index-500/30 focus-visible:outline-none"
            >
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 19-7-7 7-7M19 12H5" />
                </svg>
                Regresar
            </a>
        </div>
    </div>
</body>
</html>
