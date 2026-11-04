<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        @yield ('code')
        |
        @yield ('title')
        - CEPRE UNU
    </title>

    <meta
        name="description"
        content="Plataforma institucional del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU"
    />
    <meta name="author" content="CEPRE - UNU" />

    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />

    @fluxAppearance
    @vite (['resources/css/app.css'])
</head>
<body class="h-full w-full bg-zinc-100 text-base text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="flex h-dvh items-center justify-center p-4">
        <div
            class="mx-auto w-full max-w-lg rounded-2xl border border-zinc-200/80 bg-white p-8 text-center shadow-xs sm:p-10 dark:border-white/[0.06] dark:bg-zinc-900"
        >
            <div
                class="mx-auto mb-6 flex size-18 items-center justify-center rounded-2xl border border-index-100 bg-index-50 text-index-500 dark:border-index-500/20 dark:bg-index-500/10 dark:text-index-300"
            >
                @yield ('illustration')
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

            <flux:button variant="primary" href="/">
                <x-slot name="icon">
                    <x-ui.icon name="arrow-left" size="5" class="text-white" />
                </x-slot>
                Regresar
            </flux:button>
        </div>
    </div>

    @fluxScripts
</body>
</html>
