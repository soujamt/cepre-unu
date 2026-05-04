<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        @yield ('code')
        |
        @yield ('title')
    </title>

    <meta
        name="description"
        content="Panel de administración de la Oficina de Tecnologías de la Información de la Universidad Nacional Intercultural de la Amazonía - UNIA"
    />
    <meta name="author" content="OTI - UNIA" />

    <link rel="icon" type="image/png" href="{{ asset('assets/media/logo-dark.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap"
        rel="stylesheet"
    />

    <!-- KeenIcons -->
    <link href="{{ asset('assets/keenicons/styles.bundle.css') }}" rel="stylesheet" />

    <!-- HugeIcons -->
    <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" />

    @fluxAppearance
    @vite (['resources/css/app.css'])
</head>
<body class="text-foreground dark:text-foreground-dark h-full w-full bg-zinc-50 text-base antialiased dark:bg-zinc-800">
    <div class="flex h-dvh items-center justify-center">
        <div class="mx-auto max-w-xl p-6 text-center">
            <div class="mb-8">
                @yield ('illustration')
            </div>

            <div class="mb-4">
                <h1 class="text-7xl font-extrabold text-zinc-900 dark:text-white">
                    @yield ('code')
                </h1>
            </div>

            <h2 class="mb-3 text-3xl font-bold text-zinc-800 dark:text-zinc-100">
                @yield ('title')
            </h2>

            <p class="mb-8 text-base text-zinc-500 dark:text-zinc-400">
                @yield ('message')
            </p>

            <flux:button variant="primary" href="/" class="font-normal">
                <x-slot name="icon">
                    <i class="ki-duotone ki-arrow-left text-xl"></i>
                </x-slot>
                Regresar
            </flux:button>
        </div>
    </div>

    @fluxScripts
</body>
</html>
