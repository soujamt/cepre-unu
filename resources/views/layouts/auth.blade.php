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

    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>

<body
    class="text-foreground dark:text-foreground-dark relative min-h-screen w-full overflow-x-hidden bg-[radial-gradient(circle_at_top,rgba(132,204,22,0.12),transparent_28%),linear-gradient(180deg,#f8fafc_0%,#f1f5f9_100%)] antialiased dark:bg-[radial-gradient(circle_at_top,rgba(163,230,53,0.08),transparent_25%),linear-gradient(180deg,#09090b_0%,#111827_100%)]"
>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div
            class="absolute inset-x-0 top-0 h-64 bg-[linear-gradient(to_bottom,rgba(255,255,255,0.82),transparent)] dark:bg-[linear-gradient(to_bottom,rgba(9,9,11,0.25),transparent)]"
        ></div>
        <div
            class="absolute top-20 left-[18%] h-72 w-72 rounded-full bg-lime-400/22 blur-3xl dark:bg-lime-300/14"
        ></div>
        <div
            class="absolute right-[10%] bottom-16 h-80 w-80 rounded-full bg-sky-300/18 blur-3xl dark:bg-sky-400/10"
        ></div>
        <div
            class="absolute inset-0 [background-image:linear-gradient(to_right,rgba(15,23,42,0.7)_1px,transparent_1px),linear-gradient(to_bottom,rgba(15,23,42,0.7)_1px,transparent_1px)] [background-size:48px_48px] opacity-[0.04] dark:opacity-[0.06]"
        ></div>
        <div
            class="absolute inset-x-0 bottom-0 h-px bg-[linear-gradient(to_right,transparent,rgba(156,163,175,0.5),transparent)] dark:bg-[linear-gradient(to_right,transparent,rgba(255,255,255,0.1),transparent)]"
        ></div>
    </div>

    <div
        class="relative flex h-auto min-h-screen items-center justify-center px-4 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-16"
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
