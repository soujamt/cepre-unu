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

    @include ('partials.tema')

    <style>
        html {
            background-color: #f4f4f5;
        }

        html.dark {
            background-color: #070708;
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
