<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ isset($title) ? $title . ' - CEPRE UNU' : 'CEPRE UNU' }}</title>

    <meta
        name="description"
        content="Plataforma institucional del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU"
    />
    <meta name="author" content="CEPRE - UNU" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>

<body
    class="text-foreground dark:text-foreground-dark relative min-h-screen w-full overflow-x-hidden antialiased bg-white dark:bg-zinc-950"
>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div
            class="absolute inset-x-0 top-0 h-64 bg-[linear-gradient(to_bottom,rgba(255,255,255,0.82),transparent)] dark:bg-[linear-gradient(to_bottom,rgba(9,9,11,0.25),transparent)]"
        ></div>
        <div
            class="absolute top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-lime-400/30 blur-3xl dark:bg-lime-300/20"
        ></div>
        <div
            class="absolute inset-x-0 bottom-0 h-px bg-[linear-gradient(to_right,transparent,rgba(156,163,175,0.5),transparent)] dark:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.1),transparent)]"
        ></div>
    </div>

    <div
        class="relative flex h-auto min-h-screen items-center justify-center px-4 py-10 sm:px-6 sm:py-16 lg:px-8 lg:py-24"
    >
        {{ $slot }}
    </div>

    @persist ('toast')
        <x-notificacion />
    @endpersist

    @persist ('alert')
        <x-alerta />
    @endpersist

    @livewireScripts
    @fluxScripts
</body>
</html>
