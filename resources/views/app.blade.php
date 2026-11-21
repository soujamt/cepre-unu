<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
        name="description"
        content="Plataforma institucional del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU"
    />
    <meta name="author" content="CEPRE - UNU" />

    {{-- Aplica el tema antes de pintar. Usa la misma clave que Flux para que la preferencia sea igual en todas las pantallas. --}}
    <script>
        (function () {
            const tema = localStorage.getItem('flux.appearance') || 'system';
            const oscuro = tema === 'dark' || (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', oscuro);
            document.documentElement.style.colorScheme = oscuro ? 'dark' : 'light';
        })();
    </script>

    <style>
        html {
            background-color: #f4f4f4;
        }

        html.dark {
            background-color: #0b0b0b;
        }
    </style>

    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />

    @viteReactRefresh
    @vite (['resources/css/inertia.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    <x-inertia::head>
        <title>{{ config('app.name', 'CEPRE UNU') }}</title>
    </x-inertia::head>
</head>
<body class="font-sans antialiased">
    <x-inertia::app />
</body>
</html>
