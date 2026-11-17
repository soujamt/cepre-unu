<div class="min-h-screen bg-zinc-50 pb-16 dark:bg-zinc-950">
    <x-publico.encabezado
        titulo="Portal del postulante"
        subtitulo="Inscríbase en línea o consulte su inscripción, su aula y sus documentos."
    />

    <main class="mx-auto grid max-w-5xl gap-4 px-5 pt-4 sm:grid-cols-2">
        @foreach ([
            ['ruta' => 'publico.inscripcion', 'icono' => 'pencil-square', 'titulo' => 'Inscribirme', 'texto' => 'Primero pague en el banco. Luego ingrese su DNI y el número de operación del voucher.'],
            ['ruta' => 'publico.consulta', 'icono' => 'search', 'titulo' => 'Consultar mi inscripción', 'texto' => 'Vea su estado, turno y aula, y descargue su ficha y su carnet.'],
        ] as $opcion)
            <a
                href="{{ route($opcion['ruta']) }}"
                wire:navigate
                class="group flex items-start gap-4 rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card transition-colors hover:border-zinc-300 focus-visible:ring-2 focus-visible:ring-index-500 focus-visible:outline-none dark:border-white/[0.06] dark:bg-zinc-900 dark:hover:border-white/15"
            >
                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300">
                    <x-ui.icon :name="$opcion['icono']" size="6" />
                </span>
                <span class="min-w-0 space-y-1">
                    <span class="flex items-center gap-1 text-base font-semibold text-zinc-900 dark:text-white">
                        {{ $opcion['titulo'] }}
                        <x-ui.icon name="chevron-right" size="4" class="text-zinc-400 transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <span class="block text-sm text-pretty text-zinc-500 dark:text-zinc-400">{{ $opcion['texto'] }}</span>
                </span>
            </a>
        @endforeach
    </main>
</div>
