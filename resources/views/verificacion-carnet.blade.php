<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Verificación de carnet - CEPRE UNU</title>
    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />
    @vite (['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-10">
        <div class="mb-6 flex items-center gap-3">
            <img src="{{ asset('assets/media/logo_unu.png') }}" alt="" class="size-12" />
            <div>
                <p class="text-sm font-semibold text-zinc-900">UNIVERSIDAD NACIONAL DE UCAYALI</p>
                <p class="text-xs text-zinc-500">Centro Preuniversitario · Verificación de carnet</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div @class([
                'px-5 py-4 text-center text-lg font-semibold tracking-wide',
                'bg-green-600 text-white' => $esValido,
                'bg-red-600 text-white' => ! $esValido,
            ])>
                {{ $esValido ? 'CARNET VÁLIDO' : 'CARNET NO VÁLIDO' }}
            </div>

            <dl class="divide-y divide-zinc-100 text-sm">
                @foreach ([
                    'Estudiante' => $nombre,
                    'Documento' => $documento,
                    'Proceso' => $proceso,
                    'Sede' => $sede,
                    'Carrera' => $carrera,
                    'Turno' => $turno,
                ] as $etiqueta => $valor)
                    <div class="flex gap-4 px-5 py-3">
                        <dt class="w-24 shrink-0 text-zinc-500">{{ $etiqueta }}</dt>
                        <dd class="font-medium text-zinc-900">{{ $valor ?: '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        @unless ($esValido)
            <p class="mt-4 text-center text-sm text-zinc-500">La inscripción no está activa. Consulte en las oficinas del CEPRE-UNU.</p>
        @endunless
    </main>
</body>
</html>
